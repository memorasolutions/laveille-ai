<?php

declare(strict_types=1);

namespace Modules\Directory\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Intervention\Image\Drivers\Gd\Driver as ImageGdDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Modules\Directory\Models\Tool;
use Throwable;

class ScreenshotService
{
    /**
     * Capture le screenshot d'un outil. Ne jamais ecraser un bon screenshot par un mauvais.
     */
    public function capture(Tool $tool): bool
    {
        // Screenshot verrouillé (uploadé manuellement) : ne JAMAIS l'écraser via la capture automatique.
        if (! empty($tool->screenshot_locked)) {
            Log::channel('directory_screenshots')->info('ScreenshotService: capture ignorée (screenshot verrouillé) pour '.$tool->getTranslation('slug', 'fr_CA'));

            return false;
        }

        if (! self::isAvailable()) {
            Log::channel('directory_screenshots')->warning('ScreenshotService: Node.js ou script introuvable.');

            return false;
        }

        $slug = $tool->getTranslation('slug', 'fr_CA');
        if (empty($slug) || empty($tool->url)) {
            return false;
        }

        $outputDir = public_path('screenshots');
        if (! File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $filename = "{$slug}.jpg";
        $absolutePath = "{$outputDir}/{$filename}";

        try {
            // Capturer dans un fichier temporaire pour ne pas ecraser l'existant
            $tempPath = "{$outputDir}/_tmp_{$filename}";

            $result = Process::timeout(90)->run([
                (string) (config('services.browsershot.node_path') ?: '/usr/local/bin/node'),
                base_path('scripts/capture-screenshot.cjs'),
                $tool->url,
                $tempPath,
            ]);

            $json = json_decode(trim($result->output()), true);

            if (! is_array($json)) {
                Log::channel('directory_screenshots')->warning("Screenshot {$slug}: reponse JSON invalide");
                @unlink($tempPath);

                return false;
            }

            // Echec explicite (bloque, trop petit, erreur)
            if (($json['success'] ?? false) !== true) {
                $reason = $json['error'] ?? 'Erreur inconnue';
                $blocked = $json['blocked'] ?? false;
                $tooSmall = $json['tooSmall'] ?? false;
                Log::channel('directory_screenshots')->warning("Screenshot {$slug}: {$reason}".($blocked ? ' [BLOQUE]' : '').($tooSmall ? ' [TROP PETIT]' : ''));
                @unlink($tempPath);

                return false;
            }

            // Succes : verifier que le fichier temporaire est valide
            if (! File::exists($tempPath) || File::size($tempPath) < 5000) {
                Log::channel('directory_screenshots')->warning("Screenshot {$slug}: fichier temporaire invalide");
                @unlink($tempPath);

                return false;
            }

            $method = $json['method'] ?? 'screenshot';

            // ACTION: Brique 4 - rejet defensif si le script a deja signale un blocage, meme en
            // cas de succes apparent (le script actuel ne combine jamais success=true et
            // blocked=true, mais ce garde-fou protege contre une future evolution du script).
            // MCP: SELF (< 5 lignes de logique de garde)
            // RAISON: design doc 2026-08-10, brique 4, critere (b), CA-4.
            if (($json['blocked'] ?? false) === true) {
                Log::channel('directory_screenshots')->warning("Screenshot {$slug}: rejet - blocage signale par le script malgre success=true");
                @unlink($tempPath);
                self::cleanupMasterTempFile($json);

                return false;
            }

            // ACTION: garde-fou navigation - refuse le nouveau maitre si l'URL FINALE de la page
            // capturee n'est pas sur le domaine attendu.
            // MCP: SELF (orchestration, logique dans finalUrlDomainMatches())
            // RAISON: pilote de recaptures du 2026-08-30 - 2 captures sur 10 ont produit une image
            // de pleine hauteur parfaitement valide en apparence, mais montrant la MAUVAISE page
            // (la cascade de rejet des bandeaux cookies avait clique un lien de navigation reel).
            // Corrige a la source dans capture-screenshot.cjs (bordure de mot sur les motifs), mais
            // ce garde-fou reste la protection de dernier recours contre toute cause, connue ou
            // future, de derive de navigation - y compris un widget tiers (chat, publicite) qui
            // navigue la page hors du domaine attendu.
            if (! self::finalUrlDomainMatches($json, $tool->url)) {
                $finalUrl = is_string($json['final_url'] ?? null) ? $json['final_url'] : '(absente)';
                Log::channel('directory_screenshots')->warning("Screenshot {$slug}: rejet - URL finale hors domaine attendu ({$finalUrl}), conserve l'existant");
                @unlink($tempPath);
                self::cleanupMasterTempFile($json);

                return false;
            }

            // ACTION: Brique 3 - normalisation du fallback og:image (garde anti-bombe puis
            // cover/contain flouté) AVANT toute validation de contenu, uniquement pour ce chemin.
            // MCP: SELF (orchestration, la logique lourde vit dans normalizeOgImageFallback())
            // RAISON: design doc 2026-08-10, brique 3, CA-6, CA-8.
            if ($method === 'og:image' && ! self::normalizeOgImageFallback($tempPath)) {
                Log::channel('directory_screenshots')->warning("Screenshot {$slug}: og:image rejetee (anti-bombe ou decodage impossible)");
                @unlink($tempPath);

                return false;
            }

            // ACTION: Brique 4 - validation de contenu de la NOUVELLE image, remplace les deux
            // anciennes protections par octets (S79) devenues source de faux positifs.
            // MCP: SELF (orchestration, logique lourde dans isValidScreenshotContent())
            // RAISON: design doc 2026-08-10, brique 4, critere (b), CA-4.
            if (! self::isValidScreenshotContent($tempPath, $method)) {
                Log::channel('directory_screenshots')->warning("Screenshot {$slug}: nouvelle image invalide (contenu) - conserve l'existant");
                @unlink($tempPath);
                self::cleanupMasterTempFile($json);

                return false;
            }

            // ACTION: Brique 4 - backup .bak avant tout remplacement, etendu de l'upload manuel a
            // la capture automatique. Un seul niveau, ecrase a chaque remplacement.
            // MCP: SELF (< 5 lignes)
            // RAISON: design doc 2026-08-10, brique 4, rollback a chaud sans redeploiement.
            if (File::exists($absolutePath)) {
                @copy($absolutePath, $absolutePath.'.bak');
            }

            // Remplacer le fichier
            File::move($tempPath, $absolutePath);

            $tool->screenshot = "screenshots/{$filename}";
            // ACTION: Brique 1 - reinitialisation du focal a chaque nouvelle capture automatique
            // (un ancien focal pointerait dans le vide sur un nouveau master, decision assumee du
            // panel : repartir de 0 plutot qu'appliquer un focal obsolete).
            // MCP: SELF (< 5 lignes)
            // RAISON: design doc 2026-08-10, brique 1, CA-3 ; log explicite exige (revue
            // adversariale post-livraison) - ce comportement voulu ne doit plus rester silencieux.
            $previousFocalY = (int) ($tool->screenshot_focal_y ?? 0);
            if ($previousFocalY !== 0) {
                Log::channel('directory_screenshots')->info("Screenshot {$slug}: focal reinitialise de {$previousFocalY} a 0 suite a une nouvelle capture");
            }
            $tool->screenshot_focal_y = 0;
            // ACTION: leve le marqueur de peremption (screenshot_master_stale) - ce chemin ecrit
            // toujours un master frais a taille fixe (viewport 1200x1400, jamais de branche "trop
            // court"), donc un ecart signale par une precedente recapture manuelle trop courte est
            // desormais resolu par cette nouvelle capture automatique.
            // MCP: SELF (< 5 lignes)
            // RAISON: correctif 2026-08-14 - garde l'indicateur admin exact, jamais laisse "perime"
            // apres qu'un master a jour a ete effectivement obtenu.
            $tool->screenshot_master_stale = false;
            // ACTION: force le rafraîchissement du cache-bust ?v= même quand aucun attribut ne
            // change (recapture du même outil : chemin identique, focal déjà à 0 = rien de
            // « dirty », aucun UPDATE, donc ?v= servait l'ancienne image en cache).
            // MCP: SELF (<5 lignes)
            // RAISON: défaut préexistant relevé par la revue adversariale - le fichier physique
            // change à chaque capture, la date de mise à jour doit suivre.
            $tool->updated_at = now();
            $tool->saveQuietly();

            // ACTION: Brique 1 - deplacement atomique du master vers public/screenshots/masters/.
            // MCP: SELF (orchestration, logique dans persistMasterFile())
            // RAISON: design doc 2026-08-10, brique 1, section 3.
            self::persistMasterFile($json, $slug);

            Log::channel('directory_screenshots')->info("Screenshot {$slug}: OK via {$method} (".round(File::size($absolutePath) / 1024).' KB)');

            return true;
        } catch (Throwable $e) {
            Log::channel('directory_screenshots')->warning("Screenshot exception {$slug}: {$e->getMessage()}");
            @unlink($outputDir."/_tmp_{$filename}");
        }

        return false;
    }

    public function captureWithRetry(Tool $tool, int $maxAttempts = 3): bool
    {
        // Screenshot verrouillé : aucune (re)capture ni fallback gradient.
        if (! empty($tool->screenshot_locked)) {
            return false;
        }

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($this->capture($tool)) {
                return true;
            }

            if ($attempt < $maxAttempts) {
                sleep((int) pow(2, $attempt));
            }
        }

        // Fallback : gradient colore si toutes les tentatives echouent
        $slug = $tool->getTranslation('slug', 'fr_CA');
        $existingPath = public_path($tool->screenshot ?? '');
        if (! File::exists($existingPath) || File::size($existingPath) < 20000) {
            Log::channel('directory_screenshots')->info("Screenshot {$slug}: generation gradient fallback");

            return self::generateFallbackGradient($tool);
        }

        return false;
    }

    /**
     * Genere un gradient colore avec le nom de l'outil (fallback quand capture impossible).
     */
    public static function generateFallbackGradient(Tool $tool): bool
    {
        $slug = $tool->getTranslation('slug', 'fr_CA');
        $name = $tool->getTranslation('name', 'fr_CA');
        if (empty($slug)) {
            return false;
        }

        $outputDir = public_path('screenshots');
        if (! File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $path = "{$outputDir}/{$slug}.jpg";
        // Anti-overwrite S79 : si screenshot existant >= 5KB, on ne le remplace pas par un fallback gradient
        if (File::exists($path) && File::size($path) >= 5000) {
            Log::channel('directory_screenshots')->info("generateFallbackGradient: SKIP existant {$slug} (".File::size($path).' bytes) — anti-overwrite S79');
            $tool->screenshot = "screenshots/{$slug}.jpg";
            $tool->saveQuietly();
            return true;
        }
        $w = 1200;
        $h = 630;

        $palettes = [
            [[11, 114, 133], [26, 54, 93]],   // teal → navy
            [[26, 54, 93], [11, 114, 133]],    // navy → teal
            [[142, 68, 173], [44, 62, 80]],    // purple → dark
            [[231, 76, 60], [142, 68, 173]],   // red → purple
            [[46, 204, 113], [22, 160, 133]],  // green → teal
            [[52, 152, 219], [41, 128, 185]],  // blue → darker
            [[243, 156, 18], [211, 84, 0]],    // orange → burnt
            [[44, 62, 80], [52, 73, 94]],      // charcoal → slate
        ];

        $idx = abs(crc32($slug)) % count($palettes);
        [$c1, $c2] = $palettes[$idx];

        $img = imagecreatetruecolor($w, $h);

        // Gradient vertical par bandes (rapide)
        for ($y = 0; $y < $h; $y++) {
            $r = (float) $y / $h;
            $color = imagecolorallocate($img, (int) ($c1[0] + ($c2[0] - $c1[0]) * $r), (int) ($c1[1] + ($c2[1] - $c1[1]) * $r), (int) ($c1[2] + ($c2[2] - $c1[2]) * $r));
            imagefilledrectangle($img, 0, $y, $w, $y, $color);
        }

        $white = imagecolorallocate($img, 255, 255, 255);
        $whiteAlpha = imagecolorallocatealpha($img, 255, 255, 255, 50);

        // Police TTF (Inter SemiBold pour le nom, Regular pour le sous-titre)
        $fontBold = resource_path('fonts/Inter-SemiBold.ttf');
        $fontRegular = resource_path('fonts/Inter-Regular.ttf');
        $hasTtf = file_exists($fontBold) && file_exists($fontRegular);

        if ($hasTtf) {
            // Nom de l'outil en TTF (taille adaptative)
            $fontSize = mb_strlen($name) > 20 ? 36 : (mb_strlen($name) > 12 ? 42 : 52);
            $bbox = imagettfbbox($fontSize, 0, $fontBold, $name);
            $textW = $bbox[2] - $bbox[0];
            $textH = $bbox[1] - $bbox[7];
            $nameX = (int) (($w - $textW) / 2);
            $nameY = (int) (($h + $textH) / 2) - 20;
            imagettftext($img, $fontSize, 0, $nameX, $nameY, $white, $fontBold, $name);

            // Sous-titre dynamique
            $subSize = 18;
            $sub = str_replace(['https://', 'http://'], '', config('app.url'));
            $subBbox = imagettfbbox($subSize, 0, $fontRegular, $sub);
            $subW = $subBbox[2] - $subBbox[0];
            $subX = (int) (($w - $subW) / 2);
            imagettftext($img, $subSize, 0, $subX, $nameY + 40, $whiteAlpha, $fontRegular, $sub);
        } else {
            // Fallback GD bitmap si TTF absent
            $nameLen = strlen($name);
            $scale = 4;
            $charW = imagefontwidth(5);
            $charH = imagefontheight(5);
            $textW = $nameLen * $charW;
            $textImg = imagecreatetruecolor($textW, $charH);
            $bg = imagecolorallocate($textImg, $c1[0], $c1[1], $c1[2]);
            imagefill($textImg, 0, 0, $bg);
            imagestring($textImg, 5, 0, 0, $name, imagecolorallocate($textImg, 255, 255, 255));
            $scaledW = $textW * $scale;
            $scaledH = $charH * $scale;
            $x = (int) (($w - $scaledW) / 2);
            $y = (int) (($h - $scaledH) / 2) - 20;
            imagecopyresized($img, $textImg, max($x, 10), $y, 0, 0, min($scaledW, $w - 20), $scaledH, $textW, $charH);
            imagedestroy($textImg);
            $subLabel = str_replace(['https://', 'http://'], '', config('app.url'));
            imagestring($img, 3, (int) (($w - strlen($subLabel) * imagefontwidth(3)) / 2), $y + $scaledH + 20, $subLabel, $white);
        }

        imagejpeg($img, $path, 90);
        imagedestroy($img);

        $tool->screenshot = "screenshots/{$slug}.jpg";
        $tool->saveQuietly();

        return true;
    }

    public static function isAvailable(): bool
    {
        return file_exists((string) (config('services.browsershot.node_path') ?: '/usr/local/bin/node'))
            && file_exists(base_path('scripts/capture-screenshot.cjs'));
    }

    /**
     * Deplace le master temporaire (produit par capture-screenshot.cjs, champ master_path du
     * JSON) vers son emplacement final public/screenshots/masters/{slug}.jpg (brique 1). Absent
     * du JSON pour un fallback og:image ou un ancien format de script - aucune erreur, le focal
     * reste simplement indisponible pour cet outil (comportement documente du design doc).
     */
    private static function persistMasterFile(array $json, string $slug): void
    {
        $masterTempPath = $json['master_path'] ?? null;
        if (! is_string($masterTempPath) || $masterTempPath === '' || ! File::exists($masterTempPath)) {
            return;
        }

        $mastersDir = public_path('screenshots/masters');
        if (! File::isDirectory($mastersDir)) {
            File::makeDirectory($mastersDir, 0755, true);
        }

        $masterAbsolutePath = "{$mastersDir}/{$slug}.jpg";

        try {
            File::move($masterTempPath, $masterAbsolutePath);
        } catch (Throwable $e) {
            Log::channel('directory_screenshots')->warning("persistMasterFile: deplacement master {$slug} echoue - {$e->getMessage()}");
            @unlink($masterTempPath);
        }
    }

    /**
     * Nettoie un master temporaire orphelin lorsque la capture est finalement rejetee apres coup
     * (blocage signale, contenu invalide) - evite d'accumuler des fichiers _tmp_*.master.jpg.
     */
    private static function cleanupMasterTempFile(array $json): void
    {
        $masterTempPath = $json['master_path'] ?? null;
        if (is_string($masterTempPath) && $masterTempPath !== '' && File::exists($masterTempPath)) {
            @unlink($masterTempPath);
        }
    }

    /**
     * Garde-fou navigation (2026-08-30) - compare le domaine ENREGISTRABLE (Public Suffix List,
     * via EcosystemResolverService::extractRootDomain() - deja utilise ailleurs dans ce module,
     * correct sur les TLD composes type .co.uk) de l'URL FINALE rapportee par le script a celui de
     * l'URL demandee, JAMAIS l'URL complete : un ecart de sous-domaine (www/apex, fr./www.), de
     * schema (http/https) ou une barre oblique finale ne doivent jamais faire refuser une capture
     * par ailleurs valide.
     *
     * Tolere aussi les 230 fiches dont l'URL enregistree est elle-meme un lien de redirection
     * (ex. producthunt.com/r/p/xxx) : final_url est d'abord compare a post_redirect_url, l'URL du
     * navigateur capturee JUSTE APRES la redirection initiale mais AVANT toute interaction de la
     * cascade de rejet des bandeaux cookies/popups - cette redirection-la est deja resolue par le
     * navigateur lui-meme, gratuitement, avant que ce garde-fou n'intervienne. Seule une derive
     * SUPPLEMENTAIRE, survenue apres ce point (ex. un clic errant qui navigue vers un tiers), est
     * refusee.
     *
     * Rejette (fail-closed) si final_url est absent : un succes muet ne doit jamais etre pris pour
     * argent comptant sans preuve de la bonne page (le pilote du 2026-08-30 a mesure des echecs
     * qui ne laissaient AUCUNE ligne de journal - le silence n'est jamais une preuve de succes).
     */
    private static function finalUrlDomainMatches(array $json, string $requestedUrl): bool
    {
        $finalUrl = $json['final_url'] ?? null;
        if (! is_string($finalUrl) || $finalUrl === '') {
            return false;
        }

        $resolver = new EcosystemResolverService();
        $finalDomain = $resolver->extractRootDomain($finalUrl);
        if ($finalDomain === null) {
            return false;
        }

        $postRedirectUrl = $json['post_redirect_url'] ?? null;
        if (is_string($postRedirectUrl) && $postRedirectUrl !== '') {
            $postRedirectDomain = $resolver->extractRootDomain($postRedirectUrl);
            if ($postRedirectDomain !== null && $postRedirectDomain === $finalDomain) {
                return true;
            }
        }

        $requestedDomain = $resolver->extractRootDomain($requestedUrl);

        return $requestedDomain !== null && $requestedDomain === $finalDomain;
    }

    /**
     * Brique 3 - normalise un fallback og:image telecharge tel quel par le script Node en une
     * image 1200x630 sans jamais couper le sujet : garde anti-bombe AVANT tout decodage complet,
     * puis cover(1200,630) si le ratio est proche d'un format capture (1.2-3.0), sinon composition
     * contain sur un fond agrandi + floute (zero contenu coupe). Retourne false = rejet, le
     * fichier temporaire n'est pas modifie mais doit etre considere invalide par l'appelant.
     */
    private static function normalizeOgImageFallback(string $tempPath): bool
    {
        $maxBytes = 10 * 1024 * 1024; // 10 Mo - garde anti-bombe (CA-8)
        $maxDimensionPx = 8000;

        if (! File::exists($tempPath) || File::size($tempPath) > $maxBytes) {
            return false;
        }

        // Lecture d'en-tete seule (getimagesize), jamais de decodage complet des pixels ici.
        $declared = @getimagesize($tempPath);
        if ($declared === false || $declared[0] > $maxDimensionPx || $declared[1] > $maxDimensionPx) {
            return false;
        }

        try {
            $manager = new ImageManager(new ImageGdDriver());
            $image = $manager->read($tempPath);
            $width = $image->width();
            $height = $image->height();
            if ($width <= 0 || $height <= 0) {
                return false;
            }

            $ratio = $width / $height;

            if ($ratio >= 1.2 && $ratio <= 3.0) {
                $normalized = $image->cover(1200, 630);
            } else {
                // Fond agrandi + floute (jamais un fond uni) puis sujet complet centre dessus,
                // sans jamais depasser 1200x630 - convergence explicite du panel « zero contenu coupe ».
                $background = (clone $image)->cover(1200, 630)->blur(20);
                $foreground = (clone $image)->scale(width: 1200, height: 630);
                $normalized = $background->place($foreground, 'center');
            }

            file_put_contents($tempPath, $normalized->toJpeg(85)->toString());

            return true;
        } catch (Throwable $e) {
            Log::channel('directory_screenshots')->warning('normalizeOgImageFallback: exception - '.$e->getMessage());

            return false;
        }
    }

    /**
     * Brique 4 (b) - valide le contenu de la NOUVELLE image avant tout remplacement : decodable,
     * dimensions exactement 1200x630 pour une vraie capture Puppeteer, non quasi-uniforme
     * (page blanche/erreur). Remplace les deux anciennes protections par octets.
     */
    private static function isValidScreenshotContent(string $tempPath, string $method): bool
    {
        try {
            $manager = new ImageManager(new ImageGdDriver());
            $image = $manager->read($tempPath);
        } catch (Throwable $e) {
            Log::channel('directory_screenshots')->warning('isValidScreenshotContent: image non decodable - '.$e->getMessage());

            return false;
        }

        if ($method === 'screenshot' && ($image->width() !== 1200 || $image->height() !== 630)) {
            Log::channel('directory_screenshots')->warning("isValidScreenshotContent: dimensions inattendues ({$image->width()}x{$image->height()}) pour une capture Puppeteer");

            return false;
        }

        if (self::isQuasiUniformImage($image)) {
            Log::channel('directory_screenshots')->warning('isValidScreenshotContent: image quasi-uniforme detectee (page blanche/erreur probable)');

            return false;
        }

        return true;
    }

    /**
     * Echantillonnage en grille reguliere (10x10 par defaut) : si plus de 98% des points tombent
     * dans une teinte proche (tolerance +/-12 par canal RGB), l'image est consideree comme une
     * page blanche/erreur/placeholder plutot qu'une vraie capture.
     */
    private static function isQuasiUniformImage(ImageInterface $image, int $grid = 10, float $uniformThreshold = 0.98): bool
    {
        $width = $image->width();
        $height = $image->height();
        if ($width < $grid || $height < $grid) {
            return false; // trop petit pour un echantillonnage fiable, ne pas rejeter a tort
        }

        $tolerance = 12;
        $samples = [];
        for ($gx = 0; $gx < $grid; $gx++) {
            for ($gy = 0; $gy < $grid; $gy++) {
                $x = min((int) floor(($gx + 0.5) * $width / $grid), $width - 1);
                $y = min((int) floor(($gy + 0.5) * $height / $grid), $height - 1);
                $rgb = $image->pickColor($x, $y)->toArray();
                $samples[] = [$rgb[0] ?? 0, $rgb[1] ?? 0, $rgb[2] ?? 0];
            }
        }

        if ($samples === []) {
            return false;
        }

        [$refR, $refG, $refB] = $samples[0];
        $matching = 0;
        foreach ($samples as [$r, $g, $b]) {
            if (abs($r - $refR) <= $tolerance && abs($g - $refG) <= $tolerance && abs($b - $refB) <= $tolerance) {
                $matching++;
            }
        }

        return ($matching / count($samples)) >= $uniformThreshold;
    }

    /**
     * Purge Cloudflare ciblee d'un seul fichier (jamais un purge_everything). Extraite du
     * controleur (DirectoryAdminController::purgeCloudflareScreenshot, DRY brique 1) pour etre
     * reutilisee aussi par ScreenshotFocalService.
     */
    public static function purgeCloudflareFile(string $relativePath): void
    {
        try {
            $zoneId = config('services.cloudflare.zone_id');
            $apiToken = config('services.cloudflare.api_token');
            if (empty($zoneId) || empty($apiToken)) {
                return;
            }
            Http::timeout(8)
                ->withToken($apiToken)
                ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
                    'files' => [config('app.url').'/'.ltrim($relativePath, '/')],
                ]);
        } catch (Throwable $e) {
            Log::channel('directory_screenshots')->warning('Cloudflare purge failed: '.$e->getMessage());
        }
    }

    /**
     * Garde-fou centralisé anti-overwrite (incident S79 — 221 fichiers écrasés).
     * Refuse d'écraser un screenshot existant >= 5KB sauf si $force=true ou marqué auto-replaceable.
     * Tout writer (capture, fallback, batch og:image, GD) DOIT passer par cette méthode.
     *
     * @param  string  $absolutePath  chemin absolu cible (public_path('screenshots/...'))
     * @param  callable(string $tempPath): bool  $writer  reçoit un tempPath, retourne true si écriture OK
     * @param  bool  $force  bypass de la protection (uniquement actions admin manuelles explicites)
     * @return bool true si écrit (ou conservé existant), false si erreur
     */
    public static function safeWriteScreenshot(string $absolutePath, callable $writer, bool $force = false): bool
    {
        $minProtectedSize = 5000; // 5 KB — seuil incident S79
        if (! $force && \Illuminate\Support\Facades\File::exists($absolutePath)
            && \Illuminate\Support\Facades\File::size($absolutePath) >= $minProtectedSize) {
            \Illuminate\Support\Facades\Log::channel('directory_screenshots')->info("safeWriteScreenshot: SKIP existant {$absolutePath} (".\Illuminate\Support\Facades\File::size($absolutePath).' bytes) — anti-overwrite S79');
            return true; // Considéré comme succès (existant préservé)
        }

        // Écriture via tempPath puis move atomic
        $tempPath = $absolutePath.'.tmp.'.bin2hex(random_bytes(4));
        try {
            $ok = $writer($tempPath);
            if (! $ok || ! \Illuminate\Support\Facades\File::exists($tempPath)) {
                @unlink($tempPath);
                return false;
            }
            \Illuminate\Support\Facades\File::move($tempPath, $absolutePath);
            return true;
        } catch (Throwable $e) {
            @unlink($tempPath);
            \Illuminate\Support\Facades\Log::channel('directory_screenshots')->warning('safeWriteScreenshot exception: '.$e->getMessage());
            return false;
        }
    }
}
