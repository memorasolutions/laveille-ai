<?php

declare(strict_types=1);

namespace Modules\News\Services;

use fivefilters\Readability\Configuration;
use fivefilters\Readability\Readability;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class ContentExtractor
{
    /**
     * Extraire le contenu propre d'un article web via Readability PHP.
     *
     * ACTION : dispatcher (2026-08-31, ticket #2110) - le garde-fou de taille brute seul
     * n'a pas empêché une nouvelle exhaustion mémoire mesurée en production le jour même
     * de son déploiement, MÊME PILE D'APPEL (Masterminds\HTML5, Scanner.php:351) sur un
     * document pourtant sous le plafond : la taille brute ne prédit pas l'amplification
     * mémoire du parsing. Isole désormais l'extraction dans un sous-processus PHP jetable
     * (news:extract-isolated) - un épuisement mémoire à l'intérieur ne tue plus jamais le
     * cron news:fetch parent. Le drapeau news.extraction_isolated_process (défaut actif)
     * ne repasse en appel direct que pour les tests unitaires (Http::fake() ne peut pas
     * atteindre un vrai sous-processus).
     * MCP: SELF (dispatcher, code métier ci-dessous)
     * RAISON: isoler ce qu'on ne peut pas borner à coup sûr plutôt que de continuer à
     * deviner un plafond de taille qui vient d'échouer une fois en production.
     *
     * @return array{title: string, content: string, html: string, image: ?string, author: ?string, word_count: int}|null
     */
    public function extract(string $url): ?array
    {
        if ((bool) config('news.extraction_isolated_process', true)) {
            return $this->extractViaIsolatedProcess($url);
        }

        return $this->extractInProcess($url);
    }

    /**
     * Lance l'extraction dans un sous-processus PHP dédié, jetable, avec sa propre mémoire.
     * Un plantage du sous-processus (OOM, timeout, sortie non-JSON) est traité comme un
     * échec d'extraction ordinaire (retour null, repli sur l'accroche RSS déjà existant) -
     * jamais propagé au processus appelant.
     */
    private function extractViaIsolatedProcess(string $url): ?array
    {
        try {
            $process = Process::timeout(25)->run([
                PHP_BINARY,
                base_path('artisan'),
                'news:extract-isolated',
                $url,
                '--no-ansi',
                '--no-interaction',
            ]);
        } catch (\Throwable $e) {
            Log::channel('news_fetch')->warning("ContentExtractor: lancement du sous-processus en échec pour {$url}: {$e->getMessage()}");

            return null;
        }

        if (! $process->successful()) {
            Log::channel('news_fetch')->warning(sprintf(
                'ContentExtractor: sous-processus terminé anormalement (code %s) pour %s - probable épuisement mémoire isolé, le cron parent est intact.',
                $process->exitCode(),
                $url
            ));

            return null;
        }

        $result = json_decode(trim($process->output()), true);

        return is_array($result) ? $result : null;
    }

    /**
     * Corps réel de l'extraction (HTTP + Readability) - exécuté soit directement (tests
     * unitaires), soit à l'intérieur du sous-processus isolé lancé par
     * extractViaIsolatedProcess() via la commande news:extract-isolated.
     *
     * @return array{title: string, content: string, html: string, image: ?string, author: ?string, word_count: int}|null
     */
    public function extractInProcess(string $url): ?array
    {
        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept-Language' => 'fr-CA,fr;q=0.9,en;q=0.8',
                ])
                ->timeout(15)
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->get($url);

            if (! $response->successful()) {
                Log::warning("ContentExtractor: HTTP {$response->status()} for {$url}");

                return null;
            }

            $html = $response->body();

            // ACTION : garde-fou mémoire (2026-08-31) - une page anormalement volumineuse fait
            // exploser Masterminds\HTML5 (utilisé en interne par Readability), qui copie le
            // document plusieurs fois pendant l'analyse. Mesure en production : 391 crashs par
            // épuisement mémoire (128 Mo CLI), pile dans
            // vendor/masterminds/html5/src/HTML5/Parser/Scanner.php. Abandon avant tout appel à
            // Readability - l'appelant retombe déjà sur l'accroche RSS quand extract() renvoie
            // null (comportement existant, aucun article n'est perdu).
            // MCP: SELF (<5 lignes utiles, garde de taille)
            // RAISON: borner ce qu'on donne à une dépendance plutôt que de modifier la
            // dépendance elle-même.
            $maxBytes = (int) config('news.extraction_max_bytes', 3000000);
            if (strlen($html) > $maxBytes) {
                Log::channel('news_fetch')->warning(sprintf(
                    'ContentExtractor: page ignorée (%d octets > plafond %d) pour %s - risque d\'épuisement mémoire Readability/HTML5, repli sur l\'accroche RSS.',
                    strlen($html),
                    $maxBytes,
                    $url
                ));

                return null;
            }

            $ogImage = self::extractOgImage($html);

            // Readability PHP v3.3 : new Readability($config) puis ->parse($html)
            $config = new Configuration();
            $config->setFixRelativeURLs(true);
            $config->setOriginalURL($url);

            $readability = new Readability($config);
            $result = $readability->parse($html);

            if (! $result) {
                Log::warning("ContentExtractor: Readability failed for {$url}");

                return null;
            }

            $contentHtml = $readability->getContent() ?? '';
            $contentText = trim(strip_tags($contentHtml));
            $wordCount = str_word_count($contentText);

            if ($wordCount < 50) {
                Log::warning("ContentExtractor: too short ({$wordCount} words) for {$url}");

                return null;
            }

            return [
                'title' => $readability->getTitle() ?? '',
                'content' => $contentText,
                'html' => $contentHtml,
                'image' => $ogImage ?? $readability->getImage() ?? self::extractOgImageViaPuppeteer($url),
                'author' => $readability->getAuthor(),
                'word_count' => $wordCount,
            ];
        } catch (\Throwable $e) {
            Log::warning("ContentExtractor: exception for {$url}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Extraire l'og:image d'un HTML.
     */
    public static function extractOgImage(string $html): ?string
    {
        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/<meta[^>]+(?:property|name)=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Fallback Puppeteer : extraire og:image des sites SPA/anti-bot.
     */
    public static function extractOgImageViaPuppeteer(string $url): ?string
    {
        try {
            // `?:` et non le 2e argument de config() : la clé EXISTE et vaut null quand la
            // variable d'environnement n'est pas définie, si bien que le défaut ne s'applique
            // jamais et que la valeur nulle se propage (TypeError mesuré le 2026-08-23 sur le
            // site jumeau de ScreenshotService).
            $nodePath = (string) (config('services.browsershot.node_path') ?: '/usr/bin/node');
            $scriptPath = base_path('scripts/extract-og-image.cjs');

            if (! file_exists($scriptPath)) {
                return null;
            }

            $process = Process::timeout(20)->run([$nodePath, $scriptPath, $url]);

            if ($process->successful()) {
                $imageUrl = trim($process->output());
                if (! empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    Log::info("ContentExtractor: Puppeteer og:image found for {$url}: {$imageUrl}");

                    return $imageUrl;
                }
            }
        } catch (\Throwable $e) {
            Log::debug("ContentExtractor: Puppeteer og:image failed for {$url}: {$e->getMessage()}");
        }

        return null;
    }
}
