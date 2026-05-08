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
        // S84 #31 — Lock file STRICT pour éviter envois multiples (incident user 3+ emails dupliqués)
        // Lock 1h : bloque tout 2e envoi dans l'heure, sauf --no-email qui passe (dry-run safe)
        $lockFile = storage_path('app/health-check-report.lock');
        $isDryRun = (bool) $this->option('no-email');
        if (! $isDryRun && file_exists($lockFile)) {
            $age = time() - filemtime($lockFile);
            if ($age < 3600) {
                $this->error("🔒 Lock file actif (âge {$age}s, max 3600s) — envoi BLOQUÉ pour éviter doublon.");
                $this->warn('Pour forcer : supprime '.$lockFile.' manuellement (réservé sysadmin).');
                Log::warning('[HealthCheckReport] Skip duplicate run', ['lock_age' => $age, 'lock_file' => $lockFile]);
                return self::SUCCESS;
            }
        }
        // Crée lock seulement si on va vraiment envoyer
        if (! $isDryRun) {
            @file_put_contents($lockFile, (string) time());
        }

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

        // S84 #32 v2 — GET direct avec Range:bytes=0-1024 (1 KB max DL) : universel + simple + ~1 KB par site
        $results = [];
        $requests = function () use ($tools) {
            foreach ($tools as $tool) {
                yield $tool->id => new Request('GET', $tool->url, ['Range' => 'bytes=0-1024']);
            }
        };

        $pool = new Pool($client, $requests(), [
            'concurrency' => self::CONCURRENCY,
            'fulfilled' => function ($response, $toolId) use (&$results, $bar, $tools) {
                $code = $response->getStatusCode();
                $tool = $tools->firstWhere('id', $toolId);
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
        // S84 #32 v3 — codes "faux positifs" : sites fonctionnent en navigateur mais refusent test programmatique
        // 400 = Bad Request (Range header refusé par certains backends), 401 = Unauthorized (API/dashboard normal)
        // 403 = Forbidden (Cloudflare/anti-bot), 405 = Method Not Allowed, 406 = Not Acceptable (header refusé)
        // 429 = Too Many Requests (rate-limit), 451 = Unavailable For Legal, 503 = Service Unavailable (Cloudflare challenge)
        if (in_array($code, [400, 401, 403, 405, 406, 429, 451, 502, 503, 504, 520, 521, 522, 523, 524, 525, 526, 527])) return 'cloudflare_block';
        // 404 = vraie page d'accueil cassée (suspect), 410 = discontinued (suspect)
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
