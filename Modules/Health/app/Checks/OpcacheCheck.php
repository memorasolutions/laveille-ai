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
                // Reprise UNIQUEMENT sur une erreur de connexion (contention transitoire de
                // PHP-FPM). Surtout pas sur les reponses d'erreur HTTP : un 503 doit remonter
                // INTACT jusqu'au bloc qui distingue le mode maintenance d'une vraie panne, et
                // `throw: false` empeche Laravel de lever sur la derniere tentative echouee.
                // Sans ces deux precautions, ce correctif cassait la detection du deploiement
                // en cours - attrape par les tests avant livraison.
                ->retry(
                    (int) config('health.opcache.retry_times', 2),
                    (int) config('health.opcache.retry_sleep_ms', 500),
                    fn (Throwable $exception): bool => $exception instanceof ConnectionException,
                    throw: false
                )
                ->acceptJson()
                ->withHeaders(['X-Sante-Jeton' => (string) config('health.opcache.token')])
                ->get($this->endpointUrl());

            // Faux signal recurrent (2026-08-12) : le pipeline de deploiement execute
            // `php artisan down --retry=15` avant le rsync et `php artisan up` a la fin
            // (.github/workflows/deploy.yml). Pendant cette fenetre, Laravel repond 503 a
            // TOUTES les requetes, ce point de controle inclus - et le cron de sante qui
            // tombe dedans envoyait une alerte « intervention rapide » alors que rien
            // n'est casse. C'est la meme classe de faux signal que le temoin du
            // planificateur efface par optimize:clear, corrige en juillet 2026.
            //
            // Le mode maintenance de Laravel accompagne son 503 d'un en-tete Retry-After
            // (pose par --retry=15) ; une VRAIE saturation de PHP-FPM, elle, n'en met pas.
            // C'est donc cet en-tete - et lui seul - qui distingue une indisponibilite
            // VOULUE d'une panne. Ne jamais elargir ce silence a tous les 503.
            if ($response->status() === 503 && $response->header('Retry-After') !== '') {
                return $result->ok('Mesure OPcache ignoree : le site est en mode maintenance (deploiement en cours). Aucune action requise.');
            }

            if (! $response->successful()) {
                return $result->failed(
                    "Impossible de mesurer OPcache : le point de contrôle HTTP a répondu {$response->status()}. Vérifiez l’URL interne, le jeton et PHP-FPM."
                );
            }

            $payload = $response->json();
        } catch (ConnectionException $exception) {
            // Faux signal recurrent n°2 (2026-08-13). Ce controle avait envoye 7 courriels
            // « intervention rapide » depuis sa mise en service SANS jamais reveler un seul
            // incident : verification faite le 2026-08-13, le site repondait en 0,2 s et
            // l'endpoint aussi, au moment meme de l'alerte. La cause n'est pas une panne mais
            // la nature de la mesure : un worker PHP-FPM adresse ici une requete HTTP a son
            // PROPRE pool, sur un hebergement mutualise ou plusieurs sites executent des taches
            // chaque minute. Une contention de quelques secondes suffit a faire expirer cet
            // aller-retour alors que le site sert normalement les visiteurs.
            //
            // Un echec de connexion ISOLE n'est donc pas un signal : deux echecs CONSECUTIFS
            // en sont un. Le premier reste silencieux (ok() SANS message, seul moyen de ne pas
            // declencher de courriel - cf. la note plus bas sur getNotificationMessage()), mais
            // il est compte ; le compteur est remis a zero des qu'une mesure reussit.
            $cacheKey = (string) config('health.opcache.connection_failures_cache_key');
            $consecutiveFailures = (int) Cache::get($cacheKey, 0) + 1;
            Cache::forever($cacheKey, $consecutiveFailures);

            $meta = ['erreur' => $exception->getMessage(), 'echecs_consecutifs' => $consecutiveFailures];

            if ($consecutiveFailures >= (int) config('health.opcache.fail_after_consecutive_failures', 2)) {
                return $result->failed("Impossible de mesurer OPcache : la connexion HTTP interne a échoué {$consecutiveFailures} fois d'affilée. Vérifiez APP_URL, le serveur web et PHP-FPM.")
                    ->meta($meta);
            }

            return $result->meta($meta)->ok();
        } catch (Throwable $exception) {
            return $result->failed('Impossible de mesurer OPcache : la réponse HTTP est inexploitable. Vérifiez le point de contrôle interne.')
                ->meta(['erreur' => $exception->getMessage()]);
        }

        // La connexion a abouti : la serie d'echecs consecutifs est rompue. Remise a zero ici
        // plutot que plus bas, car c'est bien la CONNEXION que ce compteur surveille, pas la
        // qualite du JSON recu ni l'etat d'OPcache lui-meme.
        Cache::forever((string) config('health.opcache.connection_failures_cache_key'), 0);

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
