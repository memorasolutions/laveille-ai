<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Health\Checks;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Throwable;

class OpcacheCheck extends Check
{
    public function run(): Result
    {
        $result = Result::make();

        try {
            $response = Http::timeout((int) config('health.opcache.timeout', 5))
                ->acceptJson()
                ->withHeaders(['X-Sante-Jeton' => (string) config('health.opcache.token')])
                ->get($this->endpointUrl());

            if (! $response->successful()) {
                return $result->failed(
                    "Impossible de mesurer OPcache : le point de contrôle HTTP a répondu {$response->status()}. Vérifiez l’URL interne, le jeton et PHP-FPM."
                );
            }

            $payload = $response->json();
        } catch (ConnectionException $exception) {
            return $result->failed('Impossible de mesurer OPcache : la connexion HTTP interne a échoué. Vérifiez APP_URL, le serveur web et PHP-FPM.')
                ->meta(['erreur' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            return $result->failed('Impossible de mesurer OPcache : la réponse HTTP est inexploitable. Vérifiez le point de contrôle interne.')
                ->meta(['erreur' => $exception->getMessage()]);
        }

        if (! $this->isUsablePayload($payload)) {
            return $result->failed('Impossible de mesurer OPcache : le JSON reçu est incomplet. Vérifiez le point de contrôle interne et PHP-FPM.');
        }

        $statistics = $payload['opcache_statistics'];
        $memory = $payload['memory_usage'];
        $interned = $payload['interned_strings_usage'];

        $keysPercent = $this->percent((float) $statistics['num_cached_keys'], (float) $statistics['max_cached_keys']);
        $memoryTotal = (float) $memory['used_memory'] + (float) $memory['free_memory'] + (float) $memory['wasted_memory'];
        $memoryPercent = $this->percent((float) $memory['used_memory'], $memoryTotal);
        $internedPercent = $this->percent((float) $interned['used_memory'], (float) $interned['buffer_size']);
        $refusals = max(0, (int) $statistics['misses'] - (int) $statistics['num_cached_scripts']);
        $cacheKey = (string) config('health.opcache.refusals_cache_key');
        $previousRefusals = Cache::get($cacheKey);
        $refusalsDelta = is_numeric($previousRefusals) ? max(0, $refusals - (int) $previousRefusals) : 0;

        Cache::forever($cacheKey, $refusals);

        $meta = [
            'sapi' => (string) ($payload['sapi'] ?? ''),
            'keys_percent' => $keysPercent,
            'memory_percent' => $memoryPercent,
            'interned_percent' => $internedPercent,
            'refusals' => $refusals,
            'previous_refusals' => is_numeric($previousRefusals) ? (int) $previousRefusals : null,
            'refusals_delta' => $refusalsDelta,
            'cache_full' => (bool) $payload['cache_full'],
            'memory_usage' => $memory,
            'opcache_statistics' => $statistics,
            'interned_strings_usage' => $interned,
        ];
        $summary = sprintf('clés %.1f %%, mémoire %.1f %%, chaînes %.1f %%, refus +%d', $keysPercent, $memoryPercent, $internedPercent, $refusalsDelta);

        $failures = [];
        $warnings = [];

        if ((bool) $payload['cache_full']) {
            $failures[] = 'Le cache OPcache est plein : augmentez sa capacité, puis redémarrez PHP-FPM pendant une fenêtre contrôlée.';
        }

        $this->evaluatePercent($keysPercent, 'keys', 'la table des clés', $failures, $warnings);
        $this->evaluatePercent($memoryPercent, 'memory', 'la mémoire', $failures, $warnings);
        $this->evaluatePercent($internedPercent, 'interned', 'le tampon des chaînes internées', $failures, $warnings);

        // La progression de l'écart n'est un signe de REFUS que si le cache est deja sous
        // pression. Avec validate_timestamps=1, un deploiement invalide puis recompile des
        // centaines de fichiers : cela gonfle les ratés sans qu'aucun script ne soit refusé.
        // Sans ce garde, l'alerte sonnerait a chaque mise en ligne et on apprendrait a
        // l'ignorer. Constaté en production le 2026-08-01 : écart passé de 23 a 436 apres
        // un simple deploiement, alors que le cache n'etait rempli qu'a 28,7 pour cent.
        $sousPression = (bool) $payload['cache_full']
            || $keysPercent > (float) config('health.opcache.warn_keys_percent')
            || $memoryPercent > (float) config('health.opcache.warn_memory_percent');

        if ($sousPression && $refusalsDelta > (int) config('health.opcache.fail_refusals_delta', 1000)) {
            $failures[] = "OPcache a refusé {$refusalsDelta} scripts supplémentaires alors qu'il est déjà sous pression : augmentez la capacité adaptée, puis redémarrez PHP-FPM pendant une fenêtre contrôlée.";
        } elseif ($sousPression && $refusalsDelta > (int) config('health.opcache.warn_refusals_delta', 100)) {
            $warnings[] = "OPcache a refusé {$refusalsDelta} scripts supplémentaires alors qu'il approche de ses limites : surveillez la progression et préparez une augmentation de capacité.";
        }

        $result->shortSummary($summary)->meta($meta);

        if ($failures !== []) {
            return $result->failed(implode(' ', $failures));
        }

        if ($warnings !== []) {
            return $result->warning(implode(' ', $warnings));
        }

        // ok() SANS message, volontairement. Spatie envoie une notification pour tout resultat
        // dont getNotificationMessage() n'est pas vide, QUEL QUE SOIT son statut
        // (RunHealthChecksCommand ligne 116) : le filtrage sur l'echec n'intervient que si
        // only_on_failure est vrai, ce qu'on ne veut pas puisqu'on tient a etre prevenu des
        // les avertissements. Un message pose ici suffisait donc a declencher un courriel
        // « AVERTISSEMENT » disant « aucune action requise ». Constate en production le
        // 2026-08-01. L'etat sain reste lisible sur /health via shortSummary (les pourcentages).
        return $result->ok();
    }

    private function endpointUrl(): string
    {
        // Le jeton voyage par en-tête, jamais dans l'URL : une URL complète est
        // journalisée par le serveur web, un en-tête ne l'est pas.
        return rtrim((string) config('app.url'), '/').'/'.trim((string) config('health.opcache.path', '_sante/opcache'), '/');
    }

    private function percent(float $used, float $total): float
    {
        return $total > 0 ? round(($used / $total) * 100, 1) : 0.0;
    }

    /** @param mixed $payload */
    private function isUsablePayload(mixed $payload): bool
    {
        if (! is_array($payload) || isset($payload['error']) || ! isset($payload['cache_full'])) {
            return false;
        }

        foreach ([
            ['opcache_statistics', 'num_cached_scripts'], ['opcache_statistics', 'num_cached_keys'],
            ['opcache_statistics', 'max_cached_keys'], ['opcache_statistics', 'misses'],
            ['memory_usage', 'used_memory'], ['memory_usage', 'free_memory'], ['memory_usage', 'wasted_memory'],
            ['interned_strings_usage', 'used_memory'], ['interned_strings_usage', 'buffer_size'],
        ] as [$section, $key]) {
            if (! isset($payload[$section][$key]) || ! is_numeric($payload[$section][$key])) {
                return false;
            }
        }

        return true;
    }

    /** @param list<string> $failures @param list<string> $warnings */
    private function evaluatePercent(float $percent, string $configSuffix, string $label, array &$failures, array &$warnings): void
    {
        if ($percent > (float) config("health.opcache.fail_{$configSuffix}_percent")) {
            $failures[] = sprintf('L’occupation de %s atteint %.1f %% : augmentez la capacité OPcache correspondante.', $label, $percent);
        } elseif ($percent > (float) config("health.opcache.warn_{$configSuffix}_percent")) {
            $warnings[] = sprintf('L’occupation de %s atteint %.1f %% : surveillez sa progression et planifiez une augmentation.', $label, $percent);
        }
    }
}
