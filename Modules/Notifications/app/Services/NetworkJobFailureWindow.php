<?php

declare(strict_types=1);

namespace Modules\Notifications\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2246 (2026-09-10) - les travaux de fond qui dépendent du RÉSEAU (capture d'une
 * vignette de site, récupération d'une image/ressource distante, API tierce) échouent parfois
 * de façon ISOLÉE sans que rien ne soit cassé : le serveur distant était lent ou momentanément
 * indisponible. Mesuré : environ un échec sur 150 travaux en une nuit, isolé, sans cause commune.
 *
 * Règle retenue, PROPRE aux travaux réseau (branchée dans
 * AppServiceProvider::configureQueueFailureHandling(), AVANT AutomationAlertService::fire() -
 * elle ne remplace ni ne devient un seuil global, et ne touche à aucun autre type de travail) :
 * un échec isolé sur un job réseau NE déclenche PAS l'alerte, mais TROIS échecs en TRENTE
 * MINUTES sur le MÊME type de job (même classe) la déclenchent. C'est la répétition RAPPROCHÉE
 * qui porte l'information, jamais le compte cumulé : la fenêtre est GLISSANTE et s'oublie
 * d'elle-même (voir pruneExpired()) - sans cela, trois échecs étalés sur trois semaines
 * finiraient par alerter, ce qui serait absurde.
 *
 * Les jobs absents de NETWORK_JOB_CLASSES gardent le comportement EXISTANT (alerte à CHAQUE
 * échec, déjà régulée par l'anti-spam 15 minutes de AutomationAlertService) : cette classe ne
 * touche à rien d'autre.
 */
final class NetworkJobFailureWindow
{
    /**
     * Fenêtre glissante et seuil - PROPRES aux travaux réseau (ticket #2246). Ne jamais
     * réutiliser ces constantes pour un autre seuil : la règle du projet est « aucun seuil
     * global », voir le docblock de classe.
     */
    public const WINDOW_MINUTES = 30;

    public const THRESHOLD = 3;

    /**
     * Classes de jobs en file dont le travail SORT vers Internet - lu directement dans leur
     * handle() (et les services qu'il appelle), jamais deviné par leur nom (ticket #2246,
     * étape 2) :
     *   - CaptureScreenshotJob   : capture d'écran d'un site tiers (navigateur headless).
     *   - ResolveFaviconJob      : jusqu'à 3 fournisseurs de favicon distants.
     *   - PurgeCloudflareCacheJob: API Cloudflare.
     *   - DispatchWebhookJob     : endpoint webhook du CLIENT (hors de notre contrôle).
     *   - SendWebmentionsJob     : endpoints webmention distants découverts dans les liens.
     *   - ScanArticleJob         : API OpenRouter (modération IA en cascade).
     *   - AuthorActivityCheckJob: envoi de courriel de réactivation (SMTP sortant).
     *   - EnrichToolJob          : API OpenRouter (enrichissement IA, via commande Artisan).
     *   - ProcessWorkflowStep    : étape « send_email » d'un parcours (SMTP sortant).
     *   - SendCampaignEmailJob   : API Brevo.
     *   - SendDigestJob          : API Brevo.
     *   - SendWebPushNotification: protocole Web Push vers les fournisseurs de navigateurs.
     *
     * Volontairement ABSENTS (purement locaux - base de données / fichiers / journal
     * seulement, aucune sortie réseau dans leur handle()) : AutoDetectNewsToolsJob (module
     * News) et ProcessUserExport (module Auth).
     *
     * @var array<int, string>
     */
    public const NETWORK_JOB_CLASSES = [
        \Modules\Directory\Jobs\CaptureScreenshotJob::class,
        \Modules\Core\Jobs\ResolveFaviconJob::class,
        \Modules\CloudflareCache\Jobs\PurgeCloudflareCacheJob::class,
        \Modules\Webhooks\Jobs\DispatchWebhookJob::class,
        \Modules\Authors\Jobs\SendWebmentionsJob::class,
        \Modules\Authors\Jobs\ScanArticleJob::class,
        \Modules\Authors\Jobs\AuthorActivityCheckJob::class,
        \Modules\Directory\Jobs\EnrichToolJob::class,
        \Modules\Newsletter\Jobs\ProcessWorkflowStep::class,
        \Modules\Newsletter\Jobs\SendCampaignEmailJob::class,
        \Modules\Newsletter\Jobs\SendDigestJob::class,
        \Modules\Notifications\Jobs\SendWebPushNotification::class,
    ];

    public static function isNetworkJob(string $jobClass): bool
    {
        return in_array($jobClass, self::NETWORK_JOB_CLASSES, true);
    }

    /**
     * Enregistre cet échec et décide si l'alerte doit partir MAINTENANT.
     *
     * - Job non réseau : true immédiat, AUCUN enregistrement en cache - comportement existant
     *   intact pour tout le reste (aucun autre seuil touché).
     * - Job réseau : enregistre l'horodatage de cet échec, purge ce qui sort de la fenêtre
     *   glissante de self::WINDOW_MINUTES minutes, et ne renvoie true qu'à partir du
     *   self::THRESHOLD-ième échec encore présent dans cette fenêtre.
     */
    public static function shouldAlert(string $jobClass): bool
    {
        if (! self::isNetworkJob($jobClass)) {
            return true;
        }

        $cacheKey = self::cacheKey($jobClass);
        $timestamps = self::pruneExpired(self::storedTimestamps($cacheKey));

        $timestamps[] = now()->getTimestamp();

        Cache::put($cacheKey, $timestamps, now()->addMinutes(self::WINDOW_MINUTES));

        $count = count($timestamps);

        if ($count < self::THRESHOLD) {
            // ACTION: tracer l'étouffement, jamais un retour muet.
            // MCP: SELF (<5 lignes utiles, le reste est le commentaire de raison)
            // RAISON: le 25-26 août 2026, un étouffement SANS trace a déjà coûté 17 heures
            // d'aveuglement pendant que trois jobs échouaient (voir AutomationAlertService,
            // même canal dédié 'automation_alerts' - survit à LOG_LEVEL=error en production
            // car son niveau est fixé à 'info' dans config/logging.php, indépendamment du
            // réglage global).
            Log::channel('automation_alerts')->info(
                "[NetworkJobFailureWindow] Échec réseau isolé ({$count}/".self::THRESHOLD.') sous le seuil - alerte non envoyée.',
                [
                    'issue' => 'sous_seuil_reseau',
                    'job' => $jobClass,
                    'echecs_dans_la_fenetre' => $count,
                    'seuil' => self::THRESHOLD,
                    'fenetre_minutes' => self::WINDOW_MINUTES,
                ]
            );

            return false;
        }

        Log::channel('automation_alerts')->info(
            "[NetworkJobFailureWindow] Seuil réseau atteint ({$count}/".self::THRESHOLD.') dans la fenêtre - alerte déclenchée.',
            [
                'issue' => 'seuil_reseau_atteint',
                'job' => $jobClass,
                'echecs_dans_la_fenetre' => $count,
                'seuil' => self::THRESHOLD,
                'fenetre_minutes' => self::WINDOW_MINUTES,
            ]
        );

        return true;
    }

    /**
     * @return array<int, int>
     */
    private static function storedTimestamps(string $cacheKey): array
    {
        $stored = Cache::get($cacheKey, []);

        return is_array($stored) ? $stored : [];
    }

    /**
     * Purge déterministe de la fenêtre glissante : ne garde que les horodatages produits dans
     * les self::WINDOW_MINUTES DERNIÈRES minutes par rapport à MAINTENANT - c'est cette
     * relecture à CHAQUE appel (jamais un compteur qui ne fait qu'augmenter) qui rend la
     * fenêtre « glissante » et lui permet de s'oublier d'elle-même, sans tâche planifiée
     * dédiée ni fichier à nettoyer.
     *
     * $timestamps n'est PAS déclaré array<int, int> en entrée malgré le contrat habituel de
     * cette classe : il vient d'un Cache::get() externe (storedTimestamps()), une source que
     * PHPStan ne peut pas garantir intacte - is_int() ci-dessous est donc un vrai garde-fou
     * d'exécution, jamais du code mort, si une valeur corrompue ou hétérogène y atterrissait un
     * jour.
     *
     * @param  array<int, mixed>  $timestamps
     * @return array<int, int>
     */
    private static function pruneExpired(array $timestamps): array
    {
        $cutoff = now()->subMinutes(self::WINDOW_MINUTES)->getTimestamp();

        return array_values(array_filter(
            $timestamps,
            static fn (mixed $timestamp): bool => is_int($timestamp) && $timestamp > $cutoff,
        ));
    }

    private static function cacheKey(string $jobClass): string
    {
        // sha256, jamais md5 : le préréglage sécurité de tests/Architecture/ArchTest.php bannit
        // md5 PARTOUT dans le projet (voir sa liste d'exemptions nommées, catégorie « clé de
        // cache »). Simple identifiant de clé, sans sortie observable ni exigence d'ordre - le
        // changer est donc sûr (contrairement au cas Motdle documenté dans ArchTest.php) et évite
        // d'allonger cette liste pour un fichier neuf.
        return 'network_job_failure_window:'.hash('sha256', $jobClass);
    }
}
