<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Analytics\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Analytics\Contracts\AnalyticsGateway;
use Modules\Analytics\Models\AnalyticsCollectionRun;
use Modules\Analytics\Services\ContentUrlResolver;
use Throwable;

/**
 * Cycle de collecte commun aux deux sources (inertie, journal, upsert). Ce cycle est UNE seule
 * règle métier : il ne varie que par la source, l'appel au gateway et l'agrégation des lignes.
 * Jamais d'exception vers le cron : tout échec devient une ligne analytics_collection_runs.
 */
abstract class AbstractSyncCommand extends Command
{
    /** 'ga4' | 'gsc' */
    abstract protected function source(): string;

    /** @return array<string, string> libellé => valeur de configuration */
    abstract protected function requiredConfig(): array;

    /** @return array<int, array<string, mixed>> lignes brutes du gateway */
    abstract protected function fetch(AnalyticsGateway $gateway, string $date): array;

    /**
     * Fusionne les lignes qui partagent la même URL normalisée puis renvoie les attributs prêts à écrire.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, array<string, mixed>> url normalisée => attributs métriques
     */
    abstract protected function aggregate(array $rows, ContentUrlResolver $resolver): array;

    /** @return class-string<\Illuminate\Database\Eloquent\Model> */
    abstract protected function model(): string;

    public function handle(AnalyticsGateway $gateway, ContentUrlResolver $resolver): int
    {
        $date = $this->option('date') ?: Carbon::now('America/Toronto')->subDays($this->defaultDaysAgo())->toDateString();

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date) || Carbon::createFromFormat('Y-m-d', $date)->toDateString() !== $date) {
            $this->error("Date invalide : « {$date} » (format attendu AAAA-MM-JJ).");

            return self::FAILURE;
        }

        // ACTION: inertie si module désactivé ou identifiant manquant
        // SELF: garde-fou de 5 lignes
        // RAISON: le cron ne doit jamais casser ; une ligne 'skipped' explique pourquoi rien n'a été collecté
        if (! config('analytics.enabled')) {
            return $this->journal($date, 'skipped', 0, 'Module Analytics désactivé (analytics.enabled).');
        }

        $missing = array_keys(array_filter($this->requiredConfig(), fn ($value) => blank($value)));
        $credentials = (string) config('analytics.google_credentials');
        if (! in_array('GOOGLE_APPLICATION_CREDENTIALS', $missing, true) && ! is_file($credentials)) {
            $missing[] = 'GOOGLE_APPLICATION_CREDENTIALS (fichier introuvable)';
        }
        if ($missing !== []) {
            return $this->journal($date, 'skipped', 0, 'Collecte ignorée, configuration manquante : '.implode(', ', $missing).'.');
        }

        try {
            $raw = $this->fetch($gateway, $date);
            $merged = $this->aggregate($raw, $resolver);
            $model = $this->model();

            // La résolution content_id/content_type se fait ICI, à l'écriture : le passé n'est jamais re-résolu.
            DB::transaction(function () use ($merged, $model, $date, $resolver) {
                foreach ($merged as $url => $attributes) {
                    $model::updateOrCreate(
                        ['date' => $date, 'url' => $url],
                        $attributes + $resolver->resolve($url)
                    );
                }
            });
        } catch (Throwable $e) {
            // ACTION: échec de collecte ou d'écriture = ligne 'error', rien d'écrit (transaction), code 0
            // SELF: gestion d'erreur de 3 lignes
            // RAISON: ne jamais casser le cron
            return $this->journal($date, 'error', 0, 'Échec de collecte : '.mb_substr($e::class.' - '.$e->getMessage(), 0, 500));
        }

        $skipped = count($raw) - $this->countUsable($raw);
        $status = $skipped > 0 ? 'partial' : 'success';
        $message = $skipped > 0
            ? "{$skipped} ligne(s) ignorée(s) (URL vide) sur ".count($raw).'.'
            : (count($merged) === 0 ? 'Aucune ligne retournée par la source pour cette date.' : null);

        return $this->journal($date, $status, count($merged), $message);
    }

    /**
     * Décalage par défaut, en jours, de la date collectée quand --date n'est pas fourni.
     * Surchargé par source, car GSC finalise ses données environ 2 jours plus tard (voir SyncGscCommand).
     */
    protected function defaultDaysAgo(): int
    {
        return 1;
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function countUsable(array $rows): int
    {
        return count(array_filter($rows, fn ($row) => trim((string) ($row['url'] ?? '')) !== ''));
    }

    private function journal(string $date, string $status, int $rows, ?string $message): int
    {
        AnalyticsCollectionRun::updateOrCreate(
            ['source' => $this->source(), 'collected_for' => $date],
            ['ran_at' => now(), 'status' => $status, 'rows_upserted' => $rows, 'message' => $message]
        );

        $this->line("[{$this->source()}] {$date} : {$status}, {$rows} ligne(s)".($message ? " - {$message}" : ''));

        return self::SUCCESS;
    }
}
