<?php

declare(strict_types=1);

namespace Modules\News\Console;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION : opération de masse DISTINCTE annoncée hors périmètre par le correctif v1.237.5 (voir
 *          CHANGELOG.md) - régénère l'image de repli (carte-titre générée) des fiches publiées
 *          dont le titre BAKÉ dans les pixels diffère du titre réellement affiché partout
 *          ailleurs sur la page (seo_title ?? title, cf. NewsImageService::resolveFallbackTitle,
 *          seule source de vérité, réutilisée telle quelle - AUCUNE règle de résolution de titre
 *          n'est dupliquée ici). Mesuré en production le 2026-08-30 sur 4613 fiches publiées
 *          vivantes : 4493 servent cette image générée (contre 120 photos curatées), 4491
 *          bakaient un titre différent de celui affiché, 1912 depuis une source non francophone
 *          (donc un titre anglais visible sur l'aperçu sur les réseaux sociaux).
 *
 *          GARDE-FOU ABSOLU, tenu par le CODE (jamais seulement par la requête qui sélectionne) :
 *          une image curatée (NewsArticle::hasCuratedImage() - image_credit rempli, posée par la
 *          porte news:apply --image) n'est JAMAIS régénérée, même si son id est passé
 *          explicitement via --ids. Double garde, même schéma que ReprocessArticlesCommand /
 *          Actu2ImageProtectionTest.php : exclusion à la SÉLECTION (requête) ET défense en
 *          profondeur (vérifiée à nouveau juste avant l'écriture).
 *
 *          BACKUP AVANT ÉCRITURE (garde-fou zéro-casse, même schéma que RetireArticlesCommand) :
 *          les fichiers .webp/.jpg existants sont copiés sous
 *          storage/app/news-image-regen/backups/ (horodatés, jamais écrasés) avant toute
 *          régénération - storage/ n'est PAS touché par le rsync de déploiement (.github/
 *          workflows/deploy.yml), donc ce dossier survit aux déploiements suivants.
 *
 *          IDEMPOTENCE : storage/app/news-image-regen/manifest.json trace, par id de fiche, le
 *          titre RÉELLEMENT baké lors du dernier passage. Un id déjà présent avec le MÊME titre
 *          cible est sauté (déjà bon) sauf --force - relancer cette commande après une
 *          interruption ne refait jamais un travail déjà fait, et ne double-facture jamais le
 *          serveur partagé (79 domaines) en CPU inutile.
 *
 *          BORNÉ DANS LE TEMPS/VOLUME : --limit plafonne le nombre de fiches TRAITÉES par appel
 *          (défaut 25, volontairement modeste - ce serveur héberge 79 domaines, une passe ne
 *          doit jamais concurrencer le trafic). --after-id permet de reprendre un lot interrompu
 *          sans reparcourir ce qui est déjà couvert par le manifest.
 *
 * MCP: SELF (commande d'administration, écriture de fichiers - aucune génération de contenu)
 * RAISON: mandat explicite du 2026-08-30 - chiffrer le coût réel AVANT toute régénération de
 * masse, jamais dérouler les 1912 fiches dans le même cycle qu'un lot pilote de dix.
 */

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\News\Models\NewsArticle;
use Modules\News\Services\NewsImageService;

class RegenerateFallbackImagesCommand extends Command
{
    protected $signature = 'news:regenerate-fallback-images
        {--ids= : Liste explicite d\'ids séparés par des virgules (les gardes publiée/non-curatée restent actives)}
        {--non-french-only : Ne cible que les fiches dont la source n\'est pas en français (lot prioritaire du mandat)}
        {--limit=25 : Nombre maximal de fiches TRAITÉES par cet appel}
        {--after-id=0 : Reprise - ne considère que les ids strictement supérieurs, ordre croissant}
        {--dry-run : N\'écrit rien ; liste ce qui serait fait et le compte total de candidats}
        {--force : Retraite même une fiche déjà présente dans le manifest avec le même titre cible}
        {--work-dir= : Dossier manifest+backups (défaut storage/app/news-image-regen) - utile pour isoler un lot pilote ou les tests}';

    protected $description = "Régénère l'image de repli des fiches publiées dont le titre baké diffère du titre affiché (hors périmètre du correctif v1.237.5) - ne touche jamais une image curatée";

    private const DEFAULT_WORK_DIR = 'app/news-image-regen';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $afterId = max(0, (int) $this->option('after-id'));
        $isDryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $idsOption = trim((string) $this->option('ids'));
        $explicitIds = $idsOption === '' ? null : array_values(array_filter(array_map('intval', explode(',', $idsOption))));

        $baseQuery = $this->buildQuery($explicitIds, $afterId);

        // Compte AVANT limit : le vrai reste-à-faire, reproductible par cette seule commande
        // (docs/CONTRAINTES-SOUS-AGENTS.md #3 - un chiffre qui sert une décision doit être
        // reproductible par une commande).
        $totalCandidats = (clone $baseQuery)->count();

        $articles = $baseQuery->orderBy('id')->limit($limit)->get();

        $this->info("Candidats correspondant aux filtres (avant --limit) : {$totalCandidats}");
        $this->info('Fiches prises dans ce lot : '.$articles->count());

        if ($articles->isEmpty()) {
            $this->info('Rien à traiter.');

            return self::SUCCESS;
        }

        $manifest = $this->loadManifest();

        $rows = [];
        $stats = ['ok' => 0, 'skip_curated' => 0, 'skip_manifest' => 0, 'error' => 0];
        $wallTotalMs = 0.0;
        $cpuTotalMs = 0.0;

        foreach ($articles as $article) {
            // Défense en profondeur - même si la requête exclut déjà les fiches curatées,
            // vérifiée à nouveau ici juste avant toute écriture (schéma ReprocessArticlesCommand).
            if ($article->hasCuratedImage()) {
                $stats['skip_curated']++;
                $rows[] = [$article->id, 'IGNORÉ (image curatée)', '-', '-', '-', '-'];

                continue;
            }

            $bakedTitle = NewsImageService::resolveFallbackTitle($article);
            $manifestEntry = $manifest[(string) $article->id] ?? null;

            if (! $force && $manifestEntry !== null && ($manifestEntry['baked_title'] ?? null) === $bakedTitle) {
                $stats['skip_manifest']++;
                $rows[] = [$article->id, 'IGNORÉ (déjà correct)', '-', '-', '-', mb_strimwidth($bakedTitle, 0, 40, '…')];

                continue;
            }

            if ($isDryRun) {
                $rows[] = [$article->id, 'SERAIT RÉGÉNÉRÉ', '-', '-', '-', mb_strimwidth($bakedTitle, 0, 40, '…')];

                continue;
            }

            try {
                $result = $this->regenerateOne($article, $bakedTitle);
            } catch (\Throwable $e) {
                $stats['error']++;
                $rows[] = [$article->id, 'ERREUR', '-', '-', '-', $e->getMessage()];
                $this->warn("[{$article->id}] échec : ".$e->getMessage());

                continue;
            }

            if ($result === null) {
                $stats['error']++;
                $rows[] = [$article->id, 'ERREUR (generateFallbackImage a rendu null)', '-', '-', '-', '-'];

                continue;
            }

            $stats['ok']++;
            $wallTotalMs += $result['wall_ms'];
            $cpuTotalMs += $result['cpu_ms'];
            $rows[] = [
                $article->id,
                'OK',
                $result['bytes_before'],
                $result['bytes_after'],
                round($result['wall_ms'], 1),
                round($result['cpu_ms'], 1),
            ];

            $manifest[(string) $article->id] = [
                'baked_title' => $bakedTitle,
                'at' => now('America/Toronto')->toIso8601String(),
                'wall_ms' => round($result['wall_ms'], 2),
                'cpu_ms' => round($result['cpu_ms'], 2),
                'bytes_before_webp' => $result['bytes_before'],
                'bytes_after_webp' => $result['bytes_after'],
                'backup_webp' => $result['backup_webp'],
            ];
            // Écrit après CHAQUE fiche (pas seulement en fin de lot) : le runner de production
            // est borné dans le temps (45 minutes) et peut être coupé en plein lot - aucune
            // progression ne doit dépendre d'un "tout ou rien" en fin de commande.
            $this->saveManifest($manifest);
        }

        $this->table(['id', 'statut', 'octets avant', 'octets après', 'ms horloge', 'ms CPU'], $rows);

        $this->info(sprintf(
            'Résumé : %d régénérées, %d ignorées (curatée), %d ignorées (déjà bon), %d erreurs.',
            $stats['ok'],
            $stats['skip_curated'],
            $stats['skip_manifest'],
            $stats['error']
        ));

        if ($stats['ok'] > 0) {
            $avgWall = $wallTotalMs / $stats['ok'];
            $avgCpu = $cpuTotalMs / $stats['ok'];
            $this->info(sprintf(
                'Coût mesuré sur ce lot : %.1f ms horloge/image (%.1f ms CPU/image) en moyenne, %.1f s horloge au total.',
                $avgWall,
                $avgCpu,
                $wallTotalMs / 1000
            ));
        }

        $dernierIdTraite = $articles->max('id');
        $this->info("Pour reprendre après ce lot : --after-id={$dernierIdTraite}");

        return $stats['error'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<int>|null  $explicitIds
     */
    private function buildQuery(?array $explicitIds, int $afterId): \Illuminate\Database\Eloquent\Builder
    {
        $query = NewsArticle::query()
            ->with('source')
            ->published() // scopePublished() - is_published=true ET retired_at IS NULL (source unique)
            ->where(function ($q) {
                $q->whereNull('image_credit')->orWhere('image_credit', '');
            })
            ->where('id', '>', $afterId);

        if ($explicitIds !== null && $explicitIds !== []) {
            $query->whereIn('id', $explicitIds);
        } elseif ((bool) $this->option('non-french-only')) {
            $query->whereHas('source', fn ($q) => $q->where('language', '!=', 'fr'));
        }

        return $query;
    }

    /**
     * @return array{wall_ms: float, cpu_ms: float, bytes_before: int, bytes_after: int, backup_webp: ?string}|null
     */
    private function regenerateOne(NewsArticle $article, string $bakedTitle): ?array
    {
        $webpPath = public_path("storage/news/images/{$article->id}.webp");
        $jpgPath = public_path("storage/news/images/{$article->id}.jpg");

        $bytesBefore = is_file($webpPath) ? filesize($webpPath) : 0;
        $backupWebp = $this->backupIfExists($webpPath, $article->id, 'webp');
        $this->backupIfExists($jpgPath, $article->id, 'jpg');

        $ru0 = getrusage();
        $t0 = microtime(true);

        $newPath = NewsImageService::generateFallbackImage($article->id, $bakedTitle, $article->category_tag);

        $t1 = microtime(true);
        $ru1 = getrusage();

        if ($newPath === null) {
            return null;
        }

        $bytesAfter = is_file($webpPath) ? filesize($webpPath) : 0;
        if ($bytesAfter === 0) {
            throw new \RuntimeException("fichier .webp absent ou vide après génération pour l'id {$article->id}");
        }

        // Cache-bust : versionedImageUrl() (NewsArticle) suffixe ?v={updated_at} - sans ce
        // touch(), le navigateur/CDN continuerait de servir l'ancien fichier sous la même URL
        // (incident 2026-08-18, memory/reference). L'observer ne déclenche aucun effet de bord
        // sur un simple touch() : is_published reste non "dirty", donc purge de cache de liste /
        // short URL / détection d'outils restent tous inertes (vérifié dans
        // NewsArticleObserver::updated()).
        $article->touch();

        return [
            'wall_ms' => ($t1 - $t0) * 1000,
            'cpu_ms' => $this->cpuMsDelta($ru0, $ru1),
            'bytes_before' => $bytesBefore,
            'bytes_after' => $bytesAfter,
            'backup_webp' => $backupWebp,
        ];
    }

    /**
     * @param  array<string, mixed>  $ru0
     * @param  array<string, mixed>  $ru1
     */
    private function cpuMsDelta(array $ru0, array $ru1): float
    {
        $userMs = (($ru1['ru_utime.tv_sec'] ?? 0) - ($ru0['ru_utime.tv_sec'] ?? 0)) * 1000
            + (($ru1['ru_utime.tv_usec'] ?? 0) - ($ru0['ru_utime.tv_usec'] ?? 0)) / 1000;
        $sysMs = (($ru1['ru_stime.tv_sec'] ?? 0) - ($ru0['ru_stime.tv_sec'] ?? 0)) * 1000
            + (($ru1['ru_stime.tv_usec'] ?? 0) - ($ru0['ru_stime.tv_usec'] ?? 0)) / 1000;

        return $userMs + $sysMs;
    }

    private function workDir(): string
    {
        $override = trim((string) $this->option('work-dir'));

        return $override !== '' ? rtrim($override, '/') : storage_path(self::DEFAULT_WORK_DIR);
    }

    private function backupIfExists(string $path, int $articleId, string $ext): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $dir = $this->workDir().'/backups';
        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $stamp = now('America/Toronto')->format('Ymd-His');
        $dest = "{$dir}/{$articleId}-{$stamp}.{$ext}";
        File::copy($path, $dest);

        return $dest;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function loadManifest(): array
    {
        $path = $this->workDir().'/manifest.json';
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, array<string, mixed>>  $manifest
     */
    private function saveManifest(array $manifest): void
    {
        $path = $this->workDir().'/manifest.json';
        $dir = dirname($path);
        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
