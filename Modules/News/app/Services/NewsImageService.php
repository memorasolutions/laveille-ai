<?php

declare(strict_types=1);

namespace Modules\News\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class NewsImageService
{
    private function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk('public');
    }

    public function processFromUrl(string $url, int $articleId): ?string
    {
        // 2026-06-09 ANTI-RÉCIDIVE droits d'auteur (réf. PicRights 7429-5217-7374) : on NE télécharge /
        // ré-héberge PLUS aucune image de source (presse). On génère une image de marque libre à la place.
        // Le code de téléchargement ci-dessous est volontairement neutralisé (conservé pour rollback rapide).
        $article = \Modules\News\Models\NewsArticle::find($articleId);
        return self::generateFallbackImage($articleId, (string) ($article?->title ?? 'La veille IA'), $article?->category_tag ?? null);

        $maxBytes = 5 * 1024 * 1024; // 5 MB
        $maxPixels = 4000;
        $previousMemoryLimit = ini_get('memory_limit');

        try {
            $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

            // 1. HEAD pre-check Content-Length pour bloquer les gros fichiers AVANT download complet
            try {
                $head = Http::withoutVerifying()
                    ->timeout(8)
                    ->withHeaders(['User-Agent' => $userAgent])
                    ->withOptions(['allow_redirects' => ['max' => 5]])
                    ->head($url);
                $declaredSize = (int) $head->header('Content-Length');
                if ($declaredSize > 0 && $declaredSize > $maxBytes) {
                    Log::warning("NewsImage: declined oversized image {$declaredSize} bytes (max {$maxBytes}) for article {$articleId} {$url}");
                    return null;
                }
            } catch (\Throwable $headException) {
                // HEAD non supporté par certains CDN, on tente le GET avec garde
            }

            $response = Http::withoutVerifying()
                ->timeout(15)
                ->withHeaders(['User-Agent' => $userAgent])
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->get($url);

            if (! $response->successful()) {
                Log::warning("NewsImage: download failed {$url} (article {$articleId})");
                return null;
            }

            $content = $response->body();
            $size = strlen($content);

            if ($size < 5120) {
                Log::warning("NewsImage: image too small ({$size} bytes) {$url}");
                return null;
            }

            // 2. Garde post-download (sécurité si HEAD a menti)
            if ($size > $maxBytes) {
                Log::warning("NewsImage: image too large ({$size} bytes, max {$maxBytes}) for article {$articleId} {$url}");
                return null;
            }

            // 3. Check dimensions sans décodage pixels complet
            $info = @getimagesizefromstring($content);
            if (is_array($info)) {
                [$w, $h] = $info;
                if ($w > $maxPixels || $h > $maxPixels) {
                    Log::warning("NewsImage: image dimensions too large ({$w}x{$h}, max {$maxPixels}px) for article {$articleId}");
                    return null;
                }
            }

            // 4. Bump memory_limit selectif autour du decode/encode (filet de securite)
            ini_set('memory_limit', '512M');

            $manager = new ImageManager(new Driver());
            $image = $manager->read($content);
            $image->cover(1200, 630);

            $webpContent = $image->toWebp(80)->toString();
            $path = "news/images/{$articleId}.webp";
            $this->disk()->put($path, $webpContent);

            // Generation .jpg simultanee pour og:image fallback Facebook (qui ne supporte pas WebP).
            // Cf. handoff S79 #19 : Facebook crawler exige image/jpeg pour la miniature de partage.
            try {
                $jpgContent = $image->toJpeg(85)->toString();
                $this->disk()->put("news/images/{$articleId}.jpg", $jpgContent);
            } catch (\Throwable $e) {
                Log::warning("NewsImage: jpg fallback gen failed article {$articleId} - ".$e->getMessage());
            }

            return "/storage/{$path}";
        } catch (\Throwable $e) {
            Log::warning("NewsImage: exception for article {$articleId} - ".$e->getMessage());
            return null;
        } finally {
            ini_set('memory_limit', $previousMemoryLimit);
        }
    }

    /**
     * ACTION : traite un fichier d'image déposé manuellement par l'admin (écran de composition,
     * design doc "Actus - composition manuelle assistée" 2026-08-15, sections 5.3/5.4) - remplace
     * l'image de repli générée par generateFallbackImage() par le fichier rapporté de Gemini.
     * Réutilise EXACTEMENT le même recadrage et les mêmes chemins que processFromUrl() ci-dessus
     * (cover 1200x630, .webp pour la page + .jpg pour le partage social 1200x630 obligatoire) :
     * aucun nouveau pipeline, nom de fichier dérivé de l'articleId - jamais du nom d'origine du
     * fichier déposé (design doc section 5.4). La validation (type MIME réel, poids, dimensions
     * minimales) est faite par l'appelant AVANT cet appel (NewsCompositionController::uploadImage())
     * - cette méthode laisse volontairement remonter toute exception à l'appelant, contrairement à
     * processFromUrl() qui les avale : ici, l'admin doit savoir immédiatement si le dépôt a échoué.
     * MCP: SELF (Hermes model_invoke a échoué ALL_PROVIDERS_FAILED, écrit directement - signalé
     * dans le rapport de la tâche)
     * RAISON: consigne explicite du design doc - étendre NewsImageService plutôt que créer un
     * service d'images concurrent.
     */
    public function processFromUploadedFile(\Illuminate\Http\UploadedFile $file, int $articleId): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());
        $image->cover(1200, 630);

        $webpContent = $image->toWebp(80)->toString();
        $this->disk()->put("news/images/{$articleId}.webp", $webpContent);

        $jpgContent = $image->toJpeg(85)->toString();
        $this->disk()->put("news/images/{$articleId}.jpg", $jpgContent);

        return "/storage/news/images/{$articleId}.webp";
    }

    public function exists(int $articleId): bool
    {
        return $this->disk()->exists("news/images/{$articleId}.webp");
    }

    public function getPublicPath(int $articleId): string
    {
        return "/storage/news/images/{$articleId}.webp";
    }

    /**
     * Génère une image OG 1200x630 avec gradient, vrai logo SVG et titre.
     * Utilise Imagick pour le rendu SVG natif.
     */
    public static function generateFallbackImage(int $articleId, string $title, ?string $categoryTag = null): ?string
    {
        $outputPath = public_path("storage/news/images/{$articleId}.webp");
        $outputDir = dirname($outputPath);
        if (! is_dir($outputDir)) {
            \Illuminate\Support\Facades\File::makeDirectory($outputDir, 0755, true);
        }

        try {
            $w = 1200;
            $h = 630;

            // Fond dégradé qui VARIE par catégorie (tendances 2026) — couleurs foncées/saturées
            // pour garder le texte blanc lisible. Fallback déterministe par id si la catégorie est inconnue.
            // Palettes alignées sur les VRAIES catégories d'articles (clés = tag normalisé
            // sans accents). Dégradés foncés/saturés (texte blanc lisible). 2026.
            $palettes = [
                'iagenerative' => ['#064E5A', '#0B2838'],   // IA générative — teal signature
                'autre' => ['#334155', '#0f172a'],          // Autre — ardoise neutre
                'cybersecurite' => ['#4c0519', '#1a1020'],  // Cybersécurité — rouge bordeaux
                'infrastructure' => ['#0e3a5f', '#0d1b2a'], // Infrastructure — bleu acier
                'robotique' => ['#7c2d12', '#1a1208'],      // Robotique — brun orangé
                'startup' => ['#701a45', '#1a0f1a'],        // Startup — prune
                'cloud' => ['#075985', '#0c1e2e'],          // Cloud — bleu ciel foncé
                'donnees' => ['#064e3b', '#0f2027'],        // Données — vert
                'educationtech' => ['#3730a3', '#0b1020'],  // Éducation tech — indigo
                'cryptomonnaies' => ['#78350f', '#1a1208'], // Cryptomonnaies — ambre foncé
                'automobile' => ['#1e3a5f', '#111827'],     // Automobile — bleu nuit
                'fintech' => ['#065f46', '#0f2027'],        // Fintech — émeraude
                'telecom' => ['#155e63', '#0c1e2e'],        // Télécom — cyan foncé
                'hardware' => ['#374151', '#0d1117'],       // Hardware — graphite
                'santetech' => ['#0f766e', '#0c1e2e'],      // Santé tech — turquoise
                'sante' => ['#0f766e', '#0c1e2e'],          // Santé — turquoise
                'energierenouvelable' => ['#14532d', '#0b1a10'], // Énergie — vert nature
                'llm' => ['#3b0764', '#1e1b4b'],            // LLM — violet
                'default' => ['#0B7285', '#1a2332'],        // Repli — teal clair
            ];
            $catKey = '';
            if ($categoryTag) {
                $accents = ['à'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','ï'=>'i','î'=>'i','ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c'];
                $catKey = preg_replace('/[^a-z0-9]/', '', strtr(mb_strtolower($categoryTag, 'UTF-8'), $accents));
            }
            $pal = $palettes[$catKey] ?? array_values($palettes)[$articleId % count($palettes)];
            $gradient = new \Imagick();
            $gradient->newPseudoImage($w, $h, "gradient:{$pal[0]}-{$pal[1]}");

            // Overlay noir 40%
            $overlay = new \Imagick();
            $overlay->newImage($w, $h, new \ImagickPixel('rgba(0,0,0,0.4)'));
            $gradient->compositeImage($overlay, \Imagick::COMPOSITE_OVER, 0, 0);
            $overlay->destroy();

            // Motif génératif « réseau de neurones » déterministe (unique selon le titre).
            self::drawNeuralPattern($gradient, $w, $h, (string) $title, $pal);

            // Logo SVG (200x200, fond transparent, centré en haut)
            $logoPath = public_path('images/logo-eye-white.svg');
            if (file_exists($logoPath)) {
                $logo = new \Imagick();
                $logo->setBackgroundColor(new \ImagickPixel('transparent'));
                // Le logo est un SVG 52×52 : on le rasterise en haute résolution (~870px) AVANT
                // lecture, sinon resizeImage(200) part d'un raster 52px et pixelise (×3,8 upscale).
                $logo->setResolution(1200, 1200);
                $logo->readImage($logoPath);
                $logo->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);
                $logo->resizeImage(200, 200, \Imagick::FILTER_LANCZOS, 1); // downscale net
                $gradient->compositeImage($logo, \Imagick::COMPOSITE_OVER, (int) (($w - 200) / 2), 50);
                $logo->destroy();
            }

            // Titre (très gros, blanc, centré)
            $fontBold = resource_path('fonts/Inter-SemiBold.ttf');
            if (file_exists($fontBold)) {
                $len = mb_strlen($title);
                $fontSize = $len < 25 ? 64 : ($len <= 40 ? 54 : 44);

                $wrapped = wordwrap($title, 28, "\n");
                $lines = explode("\n", $wrapped);
                if (count($lines) > 3) {
                    $lines = array_slice($lines, 0, 3);
                    $lines[2] = mb_substr($lines[2], 0, 25) . '...';
                }

                $draw = new \ImagickDraw();
                $draw->setFont($fontBold);
                $draw->setFontSize($fontSize);
                $draw->setFillColor(new \ImagickPixel('white'));
                $draw->setTextAlignment(\Imagick::ALIGN_CENTER);
                $gradient->annotateImage($draw, $w / 2, 340, 0, implode("\n", $lines));
            }

            // Catégorie — badge « pill » (fond accent semi-transparent, texte majuscules).
            $fontRegular = resource_path('fonts/Inter-Regular.ttf');
            if ($categoryTag && file_exists($fontRegular)) {
                $txt = mb_strtoupper($categoryTag, 'UTF-8');
                $drawCat = new \ImagickDraw();
                $drawCat->setFont($fontRegular);
                $drawCat->setFontSize(24);
                $metrics = $gradient->queryFontMetrics($drawCat, $txt);
                $asc = abs($metrics['ascender']);
                $desc = abs($metrics['descender']);
                $pillCenterY = 500;
                $pillWidth = $metrics['textWidth'] + 48;
                $pillHeight = ($asc + $desc) + 26; // marge verticale pour accents majuscules (É, Ô…)
                $pillX1 = ($w - $pillWidth) / 2;
                $pillY1 = $pillCenterY - $pillHeight / 2;

                $pill = new \ImagickDraw();
                $pill->setFillColor(new \ImagickPixel($pal[0]));
                $pill->setFillOpacity(0.85);
                $pill->roundRectangle($pillX1, $pillY1, $pillX1 + $pillWidth, $pillY1 + $pillHeight, 16, 16);
                $gradient->drawImage($pill);

                // Baseline pour centrer verticalement le texte sur $pillCenterY :
                // centre du glyphe = baseline - (asc - desc)/2  =>  baseline = centre + (asc - desc)/2.
                $drawCat->setFillColor(new \ImagickPixel('white'));
                $drawCat->setTextAlignment(\Imagick::ALIGN_CENTER);
                $baseline = $pillCenterY + ($asc - $desc) / 2;
                $gradient->annotateImage($drawCat, $w / 2, $baseline, 0, $txt);
            }

            // Sous-titre laveille.ai
            if (file_exists($fontRegular)) {
                $drawSub = new \ImagickDraw();
                $drawSub->setFont($fontRegular);
                $drawSub->setFontSize(24);
                $drawSub->setFillColor(new \ImagickPixel('rgba(255,255,255,0.5)'));
                $drawSub->setTextAlignment(\Imagick::ALIGN_CENTER);
                $gradient->annotateImage($drawSub, $w / 2, 580, 0, 'laveille.ai');
            }

            // Genere .webp + .jpg simultanement (cf. S79 #19 fallback Facebook).
            $gradient->setCompressionQuality(85);
            $gradient->setImageFormat('webp');
            $gradient->writeImage($outputPath);

            $jpgPath = preg_replace('/\.webp$/', '.jpg', $outputPath);
            try {
                $gradient->setImageFormat('jpeg');
                $gradient->writeImage($jpgPath);
            } catch (\Throwable $e) {
                Log::warning("NewsImage gradient jpg failed article {$articleId}: ".$e->getMessage());
            }
            $gradient->destroy();

            return "/storage/news/images/{$articleId}.webp";
        } catch (\Throwable $e) {
            Log::warning("NewsImage fallback failed for article {$articleId}: " . $e->getMessage());

            return null;
        }
    }

    /**
     * Superpose un motif génératif « réseau de neurones » déterministe (nœuds + arêtes).
     * Même $seed (titre) => même motif. Subtil (faibles opacités), évite la bande du titre.
     */
    private static function drawNeuralPattern(\Imagick $canvas, int $w, int $h, string $seed, array $pal): void
    {
        $s = crc32($seed);
        $rand01 = function () use (&$s): float {
            $s = ($s * 1103515245 + 12345) & 0x7fffffff;
            return $s / 0x7fffffff;
        };

        $nodeCount = 16 + (int) ($rand01() * 5); // 16 à 20
        $nodes = [];
        for ($i = 0; $i < $nodeCount; $i++) {
            // Marges latérales uniquement : index pair => gauche, impair => droite.
            // y borné à [20,470] pour épargner la bande du titre ET le footer (catégorie/laveille.ai).
            $x = ($i % 2 === 0) ? 20 + $rand01() * 360 : 820 + $rand01() * 360;
            $y = 20 + $rand01() * 450;
            $r = 3 + $rand01() * 5;
            $nodes[] = [$x, $y, $r];
        }

        $bigIndices = [];
        for ($i = 0; $i < 3; $i++) {
            $bigIndices[] = (int) ($rand01() * $nodeCount);
        }

        $draw = new \ImagickDraw();
        $draw->setStrokeWidth(1.5);
        $draw->setStrokeColor(new \ImagickPixel('rgba(255,255,255,0.10)'));
        $draw->setFillOpacity(0);

        for ($i = 0; $i < $nodeCount; $i++) {
            for ($j = $i + 1; $j < $nodeCount; $j++) {
                $dx = $nodes[$i][0] - $nodes[$j][0];
                $dy = $nodes[$i][1] - $nodes[$j][1];
                if (sqrt($dx * $dx + $dy * $dy) < 300) {
                    $draw->line($nodes[$i][0], $nodes[$i][1], $nodes[$j][0], $nodes[$j][1]);
                }
            }
        }

        foreach ($nodes as $idx => [$x, $y, $r]) {
            $isBig = in_array($idx, $bigIndices, true);
            $radius = $isBig ? 9 + $rand01() * 2 : $r;
            $opacity = $isBig ? 0.16 : 0.22;
            $usePal = ($rand01() < 0.25) && ! $isBig;
            $color = $usePal ? $pal[0] : '#ffffff';

            $draw->setStrokeWidth(0);
            $draw->setStrokeColor(new \ImagickPixel('rgba(0,0,0,0)'));
            $draw->setFillColor(new \ImagickPixel($color));
            $draw->setFillOpacity($opacity);
            $draw->circle($x, $y, $x + $radius, $y);

            if ($isBig) {
                $draw->setStrokeWidth(1.5);
                $draw->setStrokeColor(new \ImagickPixel($color));
                $draw->setFillOpacity(0);
                $draw->circle($x, $y, $x + $radius + 6, $y);
            }
        }

        $canvas->drawImage($draw);
    }
}
