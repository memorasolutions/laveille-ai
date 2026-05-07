<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Directory\Mail\HealthCheckReportMail;
use Modules\Directory\Models\Tool;

/**
 * Vérifie la santé HTTP de tous les outils published de l'annuaire et envoie
 * un rapport email admin (PAS d'auto-mark — l'admin valide manuellement).
 *
 * @author MEMORA solutions <info@memora.ca>
 */
class HealthCheckReportCommand extends Command
{
    protected $signature = 'directory:health-check-report
                            {--limit=0 : Limiter le nombre d\'outils à vérifier (0 = tous)}
                            {--no-email : Skip envoi email (pour test CLI)}';

    protected $description = 'Vérifie la disponibilité HTTP des outils annuaire et envoie un rapport email admin (read-only, pas d\'auto-mark)';

    private const TIMEOUT = 10;

    private const CONCURRENCY = 8;

    public function handle(): int
    {
        // S84 #31 — Lock file pour éviter envois multiples (incident user 3 emails dupliqués)
        $lockFile = storage_path('app/health-check-report.lock');
        if (file_exists($lockFile) && time() - filemtime($lockFile) < 3600) {
            $this->warn('Lock file actif (<1h) — un autre run en cours ou récent. Skip pour éviter envoi dupliqué.');
            return self::SUCCESS;
        }
        @file_put_contents($lockFile, (string) time());

        $query = Tool::published()
            ->whereNotNull('url')
            ->where('lifecycle_status', '!=', 'closed') // skip déjà marqués closed
            ->select(['id', 'name', 'slug', 'url', 'lifecycle_status']);

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }
        $tools = $query->get();
        $total = $tools->count();

        if ($total === 0) {
            $this->info('Aucun outil à vérifier.');
            return self::SUCCESS;
        }

        $this->info("Vérification de {$total} outil(s) en parallèle (concurrency=".self::CONCURRENCY.')');
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // S84 #32 — User-Agent navigateur réaliste pour éviter blocages bot (Cloudflare, anti-bot, etc.)
        $client = new Client([
            'timeout' => self::TIMEOUT,
            'connect_timeout' => self::TIMEOUT,
            'allow_redirects' => ['max' => 5, 'strict' => false, 'protocols' => ['http', 'https']],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'fr-CA,fr;q=0.9,en;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, br',
            ],
            'http_errors' => false,
            'verify' => false, // certains sites ont SSL invalides mais sont up
        ]);

        $results = [];
        $requests = function () use ($tools) {
            foreach ($tools as $tool) {
                yield $tool->id => new Request('HEAD', $tool->url);
            }
        };

        $needsGetRetry = []; // S84 #32 : pour retry GET sur 405/403/501 (sites refusent HEAD)
        $pool = new Pool($client, $requests(), [
            'concurrency' => self::CONCURRENCY,
            'fulfilled' => function ($response, $toolId) use (&$results, &$needsGetRetry, $bar, $tools) {
                $code = $response->getStatusCode();
                $tool = $tools->firstWhere('id', $toolId);
                // 405 Method Not Allowed / 501 Not Implemented / 403 (parfois) : retry avec GET
                if (in_array($code, [403, 405, 501])) {
                    $needsGetRetry[$toolId] = $tool;
                    $bar->advance();
                    return;
                }
                $results[$toolId] = [
                    'tool' => $tool,
                    'status' => $code,
                    'category' => $this->categorize($code),
                    'error' => null,
                ];
                $bar->advance();
            },
            'rejected' => function ($reason, $toolId) use (&$results, $bar, $tools) {
                $tool = $tools->firstWhere('id', $toolId);
                $msg = $reason instanceof \Throwable ? $reason->getMessage() : (string) $reason;
                $results[$toolId] = [
                    'tool' => $tool,
                    'status' => 0,
                    'category' => $this->categorizeError($msg),
                    'error' => substr($msg, 0, 120),
                ];
                $bar->advance();
            },
        ]);
        $pool->promise()->wait();

        // S84 #32 — 2e passe : GET avec Range:bytes=0-1024 pour les sites refusant HEAD
        if (! empty($needsGetRetry)) {
            $this->newLine();
            $this->info('Retry GET sur '.count($needsGetRetry).' outil(s) ayant refusé HEAD (405/403/501)...');
            $getBar = $this->output->createProgressBar(count($needsGetRetry));
            $getBar->start();
            $getRequests = function () use ($needsGetRetry) {
                foreach ($needsGetRetry as $toolId => $tool) {
                    yield $toolId => new Request('GET', $tool->url, ['Range' => 'bytes=0-1024']);
                }
            };
            $getPool = new Pool($client, $getRequests(), [
                'concurrency' => self::CONCURRENCY,
                'fulfilled' => function ($response, $toolId) use (&$results, $getBar, $needsGetRetry) {
                    $code = $response->getStatusCode();
                    $results[$toolId] = [
                        'tool' => $needsGetRetry[$toolId],
                        'status' => $code,
                        'category' => $this->categorize($code),
                        'error' => null,
                    ];
                    $getBar->advance();
                },
                'rejected' => function ($reason, $toolId) use (&$results, $getBar, $needsGetRetry) {
                    $msg = $reason instanceof \Throwable ? $reason->getMessage() : (string) $reason;
                    $results[$toolId] = [
                        'tool' => $needsGetRetry[$toolId],
                        'status' => 0,
                        'category' => $this->categorizeError($msg),
                        'error' => substr($msg, 0, 120),
                    ];
                    $getBar->advance();
                },
            ]);
            $getPool->promise()->wait();
            $getBar->finish();
            $this->newLine();
        }
        $bar->finish();
        $this->newLine(2);

        $stats = ['ok' => 0, 'redirect' => 0, 'client_error' => 0, 'server_error' => 0, 'timeout' => 0, 'dns' => 0, 'refused' => 0, 'cloudflare_block' => 0];
        $suspects = [];
        foreach ($results as $r) {
            $cat = $r['category'];
            $stats[$cat] = ($stats[$cat] ?? 0) + 1;
            if (! in_array($cat, ['ok', 'redirect', 'cloudflare_block'])) {
                $suspects[] = $r;
            }
        }

        $this->table(['Catégorie', 'Nombre'], collect($stats)->map(fn ($n, $k) => [$k, $n])->values()->all());
        $this->info('Outils suspects (à vérifier manuellement) : '.count($suspects));

        if (count($suspects) === 0) {
            $this->info('✅ Aucun outil suspect — pas d\'email envoyé.');
            return self::SUCCESS;
        }

        if ($this->option('no-email')) {
            $this->warn('--no-email actif, skip envoi.');
            foreach (array_slice($suspects, 0, 10) as $s) {
                $tool = $s['tool'];
                $this->line("  • [{$s['category']} {$s['status']}] id={$tool->id} ".($tool->name ?? '?').' → '.$tool->url);
            }
            return self::SUCCESS;
        }

        $adminEmail = config('app.superadmin_email') ?: config('app.admin_email') ?: env('ADMIN_EMAIL') ?: env('MAIL_FROM_ADDRESS');
        if (! $adminEmail) {
            $this->error('Pas d\'email admin configuré (app.superadmin_email / ADMIN_EMAIL / MAIL_FROM_ADDRESS).');
            return self::FAILURE;
        }

        try {
            Mail::to($adminEmail)->send(new HealthCheckReportMail($total, $stats, $suspects));
            $this->info("✉️  Email envoyé à {$adminEmail} avec ".count($suspects).' outils suspects.');
            Log::info('[HealthCheckReport] Sent', ['email' => $adminEmail, 'total' => $total, 'suspects' => count($suspects)]);
        } catch (\Throwable $e) {
            $this->error('Email échec : '.$e->getMessage());
            Log::warning('[HealthCheckReport] Mail failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function categorize(int $code): string
    {
        if ($code >= 200 && $code < 300) return 'ok';
        if ($code >= 300 && $code < 400) return 'redirect';
        if ($code === 403 || $code === 503) return 'cloudflare_block'; // Cloudflare protection probable
        if ($code >= 400 && $code < 500) return 'client_error';
        if ($code >= 500) return 'server_error';
        return 'unknown';
    }

    private function categorizeError(string $msg): string
    {
        $msg = strtolower($msg);
        if (str_contains($msg, 'connection refused') || str_contains($msg, 'connection reset')) return 'refused';
        if (str_contains($msg, 'could not resolve') || str_contains($msg, 'name or service not known') || str_contains($msg, 'nxdomain')) return 'dns';
        if (str_contains($msg, 'timed out') || str_contains($msg, 'timeout')) return 'timeout';
        return 'refused';
    }
}
