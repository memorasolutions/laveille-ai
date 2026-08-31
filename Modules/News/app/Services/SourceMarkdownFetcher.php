<?php

declare(strict_types=1);

namespace Modules\News\Services;

use fivefilters\Readability\Configuration;
use fivefilters\Readability\Readability;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * Récupération automatique du texte source en Markdown, à la sélection d'une actualité dans
 * l'écran de composition (design doc "Actus - composition manuelle assistée" 2026-08-15, section
 * "Récupération automatique Markdown + Publier-et-purger (2026-08-17)"). Point d'entrée unique :
 * NewsCompositionController::fetchSource().
 *
 * Réutilise la logique HTTP de Modules\News\Services\ContentExtractor (même user-agent
 * navigateur, même Readability PHP) et le modèle Puppeteer de scripts/extract-og-image.cjs (même
 * scripts/extract-article.cjs), mais avec deux différences volontaires par rapport à
 * ContentExtractor, propres à ce chemin NOUVEAU : vérification TLS ACTIVE (jamais
 * withoutVerifying - ContentExtractor reste inchangé, il alimente un flux moins sensible que la
 * composition manuelle), et zéro retry (un seul essai, timeout court, échec explicite immédiat
 * sur 403/429 plutôt que de s'acharner sur un éditeur qui refuse délibérément l'accès).
 *
 * IMPORTANT (art. 41.1 de la Loi sur le droit d'auteur) : ce service ne fait JAMAIS de
 * contournement de paywall, d'authentification ou de CAPTCHA. Il lit le HTML strictement tel que
 * servi publiquement (requête anonyme ou rendu de page anonyme) ; un mur d'abonnement détecté
 * fait échouer la récupération avec un message invitant à coller le texte manuellement - jamais
 * une tentative de connexion, de résolution de CAPTCHA ou de contournement technique.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class SourceMarkdownFetcher
{
    private const HTTP_TIMEOUT_SECONDS = 12;

    private const PUPPETEER_TIMEOUT_SECONDS = 20;

    private const MIN_WORD_COUNT = 50;

    /**
     * Sous-chaînes (toutes en minuscules) qui trahissent un mur d'abonnement ou une bannière de
     * consentement dominante plutôt qu'un article. Liste volontairement courte et précise (FR +
     * EN, les deux langues servies par le site) - un faux positif bloquerait une récupération
     * légitime.
     */
    private const PAYWALL_MARKERS = [
        'abonnez-vous pour lire', "abonnez-vous pour continuer", 's\'abonner pour lire',
        'réservé aux abonnés', 'contenu réservé aux abonné', 'connectez-vous pour lire',
        'connectez-vous pour continuer', 'se connecter pour lire', 'devenez abonné',
        'subscribe to read', 'subscribe to continue reading', 'subscribe now to continue',
        'this content is for subscribers', 'this article is for subscribers',
        'log in to continue reading', 'sign in to continue reading',
    ];

    /**
     * Récupère et convertit en Markdown le contenu de l'article à $url. Validation tout-ou-rien :
     * soit ['success' => true] avec un Markdown exploitable et non vide, soit
     * ['success' => false] avec une erreur précise - jamais de résultat partiel silencieusement
     * dégradé.
     *
     * @return array{success: bool, markdown: ?string, error: ?string, acquisition: array}
     */
    public function fetch(string $url, ?string $expectedTitle = null): array
    {
        $guardError = $this->guardUrl($url);
        if ($guardError !== null) {
            return $this->failure($guardError);
        }

        $httpAttempt = $this->fetchViaHttp($url);

        if ($httpAttempt['fatal']) {
            return $this->failure($httpAttempt['error'], ['http_status' => $httpAttempt['httpStatus']]);
        }

        $method = 'http';
        $finalUrl = $httpAttempt['finalUrl'] ?? $url;
        $httpStatus = $httpAttempt['httpStatus'];
        $extraction = $httpAttempt['extraction'];

        // Étape 2 - repli Puppeteer, uniquement si l'étape HTTP a échoué ou produit un contenu
        // invalide (jamais sur un 403/429, déjà traité comme fatal ci-dessus).
        if ($extraction === null) {
            $puppeteerAttempt = $this->fetchViaPuppeteer($url);

            if ($puppeteerAttempt['error'] !== null) {
                return $this->failure($puppeteerAttempt['error'], ['http_status' => $httpStatus]);
            }

            $method = 'puppeteer';
            $finalUrl = $url;
            $extraction = $puppeteerAttempt['extraction'];
        }

        $markdown = $this->convertToMarkdown($extraction['contentHtml']);
        $wordCount = str_word_count($extraction['contentText']);

        // Le mur d'abonnement se vérifie AVANT le plancher de mots : une page de paywall n'expose
        // souvent qu'un court teaser (quelques dizaines de mots), qui passerait sinon pour un
        // "contenu trop court" générique au lieu du message explicite et actionnable ci-dessous.
        if ($this->looksLikePaywall($extraction['contentText'])) {
            return $this->failure(
                'accès restreint (mur d\'abonnement) - colle le texte manuellement.',
                ['method' => $method, 'http_status' => $httpStatus, 'word_count' => $wordCount]
            );
        }

        if ($wordCount < self::MIN_WORD_COUNT) {
            return $this->failure(
                "Contenu extrait trop court ({$wordCount} mots, minimum ".self::MIN_WORD_COUNT.') - probablement pas le corps de l\'article.',
                ['method' => $method, 'http_status' => $httpStatus, 'word_count' => $wordCount]
            );
        }

        $warning = $this->titleMismatchWarning($extraction['title'], $expectedTitle);

        return [
            'success' => true,
            'markdown' => $markdown,
            'error' => null,
            'acquisition' => [
                'method' => $method,
                'final_url' => $finalUrl,
                'http_status' => $httpStatus,
                'word_count' => $wordCount,
                'fetched_at' => now('America/Toronto')->toIso8601String(),
                'raw_markdown_hash' => hash('sha256', $markdown),
                'warning' => $warning,
            ],
        ];
    }

    /**
     * Étape 1 - requête HTTP directe, TLS vérifié, un seul essai, aucun retry. 403/429 → échec
     * "fatal" (arrêt immédiat, pas de repli Puppeteer - s'acharner ne changerait rien à un refus
     * délibéré de l'éditeur). Toute autre non-réussite (timeout, 404, 500, Readability en échec,
     * contenu vide) retourne extraction=null pour laisser le repli Puppeteer s'exécuter.
     *
     * @return array{extraction: ?array, fatal: bool, error: ?string, httpStatus: ?int, finalUrl: ?string}
     */
    private function fetchViaHttp(string $url): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept-Language' => 'fr-CA,fr;q=0.9,en;q=0.8',
            ])
                ->timeout(self::HTTP_TIMEOUT_SECONDS)
                ->connectTimeout(self::HTTP_TIMEOUT_SECONDS)
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->get($url);
        } catch (\Throwable $e) {
            return ['extraction' => null, 'fatal' => false, 'error' => null, 'httpStatus' => null, 'finalUrl' => null];
        }

        $status = $response->status();

        if (in_array($status, [403, 429], true)) {
            return [
                'extraction' => null,
                'fatal' => true,
                'error' => "accès refusé par l'éditeur (HTTP {$status}).",
                'httpStatus' => $status,
                'finalUrl' => null,
            ];
        }

        if (! $response->successful()) {
            return ['extraction' => null, 'fatal' => false, 'error' => null, 'httpStatus' => $status, 'finalUrl' => null];
        }

        $finalUrl = (string) ($response->effectiveUri() ?? $url);
        $extraction = $this->extractReadability($response->body(), $finalUrl);

        return ['extraction' => $extraction, 'fatal' => false, 'error' => null, 'httpStatus' => $status, 'finalUrl' => $finalUrl];
    }

    /**
     * Étape 2 - repli Puppeteer (scripts/extract-article.cjs), calqué sur
     * ContentExtractor::extractOgImageViaPuppeteer(). Rend la page côté navigateur headless puis
     * repasse par le même parse Readability côté PHP - une seule implémentation d'extraction.
     *
     * @return array{extraction: ?array, error: ?string}
     */
    private function fetchViaPuppeteer(string $url): array
    {
        $nodePath = config('services.browsershot.node_path') ?: '/usr/bin/node';
        $scriptPath = base_path('scripts/extract-article.cjs');

        if (! file_exists($scriptPath)) {
            return ['extraction' => null, 'error' => 'script de repli Puppeteer introuvable.'];
        }

        try {
            $process = Process::timeout(self::PUPPETEER_TIMEOUT_SECONDS)->run([$nodePath, $scriptPath, $url]);
        } catch (\Throwable $e) {
            return ['extraction' => null, 'error' => 'le repli Puppeteer a échoué : '.$e->getMessage()];
        }

        if (! $process->successful()) {
            return ['extraction' => null, 'error' => 'le repli Puppeteer n\'a pas pu rendre la page (site protégé ou introuvable).'];
        }

        $html = $process->output();
        if (trim($html) === '') {
            return ['extraction' => null, 'error' => 'le repli Puppeteer a rendu une page vide.'];
        }

        $extraction = $this->extractReadability($html, $url);
        if ($extraction === null) {
            return ['extraction' => null, 'error' => 'contenu illisible même après rendu de la page (Readability en échec).'];
        }

        return ['extraction' => $extraction, 'error' => null];
    }

    /**
     * Parse Readability partagé par les deux étapes (HTTP et Puppeteer) - une seule
     * implémentation d'extraction, même réglages que ContentExtractor::extract().
     *
     * @return array{title: string, contentHtml: string, contentText: string}|null
     */
    private function extractReadability(string $html, string $url): ?array
    {
        // ACTION : même garde-fou mémoire que ContentExtractor::extract() (2026-08-31, même
        // clé de config news.extraction_max_bytes) - Masterminds\HTML5 copie le document
        // plusieurs fois pendant l'analyse et peut épuiser le plafond mémoire CLI sur une page
        // volumineuse. Retour null = même chemin d'échec que Readability lui-même ci-dessous,
        // déjà géré par les deux appelants (fetchViaHttp/fetchViaPuppeteer).
        // MCP: SELF (<5 lignes utiles, garde de taille)
        // RAISON: borner ce qu'on donne à une dépendance plutôt que de modifier la dépendance.
        if (strlen($html) > (int) config('news.extraction_max_bytes', 3000000)) {
            return null;
        }

        try {
            $config = new Configuration();
            $config->setFixRelativeURLs(true);
            $config->setOriginalURL($url);

            $readability = new Readability($config);
            $result = $readability->parse($html);

            if (! $result) {
                return null;
            }

            $contentHtml = $readability->getContent() ?? '';
            $contentText = trim(strip_tags($contentHtml));

            if ($contentText === '') {
                return null;
            }

            return [
                'title' => $readability->getTitle() ?? '',
                'contentHtml' => $contentHtml,
                'contentText' => $contentText,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * HTML Readability → Markdown (league/html-to-markdown) : liens et titres conservés, HTML
     * résiduel strippé (attributs de mise en forme, balises non convertibles).
     */
    private function convertToMarkdown(string $contentHtml): string
    {
        $converter = new HtmlConverter([
            'strip_tags' => true,
            'remove_nodes' => 'script style noscript iframe form button',
            'hard_break' => false,
        ]);

        return trim($converter->convert($contentHtml));
    }

    /**
     * Détection de marqueurs de mur d'abonnement ou de bannière de consentement dominante :
     * comparaison insensible à la casse sur le texte extrait (jamais le HTML brut - un marqueur
     * dans un commentaire ou un script ne doit pas déclencher un faux positif).
     */
    private function looksLikePaywall(string $contentText): bool
    {
        $lower = mb_strtolower($contentText);

        foreach (self::PAYWALL_MARKERS as $marker) {
            if (str_contains($lower, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Comparaison grossière du titre extrait au titre attendu (paramètre optionnel, ex. le titre
     * déjà collecté par RSS). Une différence marquée n'est JAMAIS bloquante - seulement un
     * avertissement non bloquant dans acquisition['warning'], l'admin reste juge (l'extraction
     * peut légitimement diverger : sous-titre, article mis à jour, etc.).
     */
    private function titleMismatchWarning(string $extractedTitle, ?string $expectedTitle): ?string
    {
        $expectedTitle = trim((string) $expectedTitle);
        $extractedTitle = trim($extractedTitle);

        if ($expectedTitle === '' || $extractedTitle === '') {
            return null;
        }

        similar_text(mb_strtolower($extractedTitle), mb_strtolower($expectedTitle), $percent);

        if ($percent < 40.0) {
            return "Le titre extrait (« {$extractedTitle} ») diffère nettement du titre attendu (« {$expectedTitle} ») - vérifie qu'il s'agit bien du même article.";
        }

        return null;
    }

    /**
     * Garde SSRF légère : schéma http/https seulement, hôte résolu et vérifié PUBLIC (ni privé,
     * ni de bouclage, ni réservé) avant toute requête sortante - qu'elle soit HTTP directe ou via
     * le navigateur Puppeteer, qui partage donc la même garde en amont.
     */
    private function guardUrl(string $url): ?string
    {
        $parts = parse_url($url);

        if (! $parts || empty($parts['scheme']) || empty($parts['host'])) {
            return 'URL invalide.';
        }

        $scheme = strtolower($parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return "schéma d'URL non autorisé (« {$scheme} ») - seuls http et https sont acceptés.";
        }

        $host = $parts['host'];

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->isPublicIp($host) ? null : "adresse IP interdite (privée, de bouclage ou réservée) : {$host}.";
        }

        $ips = array_merge($this->resolveIpv4($host), $this->resolveIpv6($host));

        if ($ips === []) {
            // Environnement de test : aucun accès réseau réel, donc les hôtes fictifs des fixtures
            // (ex. exemple.com, exemple-editeur.com) ne résolvent jamais - la garde SSRF ne doit
            // pas bloquer Http::fake()/Process::fake() à cause de ça. Fail-open UNIQUEMENT sous
            // app()->environment('testing') (même bascule que VerifyRecaptcha) ; en production la
            // résolution échouée reste bloquante (fail-closed, comportement inchangé).
            if (app()->environment('testing')) {
                return null;
            }

            return "impossible de résoudre l'hôte : {$host}.";
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                return "l'hôte « {$host} » résout vers une adresse interdite (privée, de bouclage ou réservée) : {$ip}.";
            }
        }

        return null;
    }

    /**
     * Vrai si $ip n'est ni privée, ni de bouclage, ni réservée (IANA) - IPv4 comme IPv6, un seul
     * appel filter_var combinant les deux drapeaux d'exclusion.
     */
    private function isPublicIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    /**
     * @return list<string>
     */
    private function resolveIpv4(string $host): array
    {
        $ips = @gethostbynamel($host);

        return is_array($ips) ? $ips : [];
    }

    /**
     * @return list<string>
     */
    private function resolveIpv6(string $host): array
    {
        $records = @dns_get_record($host, DNS_AAAA) ?: [];

        return array_values(array_filter(array_column($records, 'ipv6')));
    }

    /**
     * @return array{success: bool, markdown: ?string, error: ?string, acquisition: array}
     */
    private function failure(string $error, array $partialAcquisition = []): array
    {
        return [
            'success' => false,
            'markdown' => null,
            'error' => $error,
            'acquisition' => array_merge([
                'method' => null,
                'final_url' => null,
                'http_status' => null,
                'word_count' => null,
                'fetched_at' => now('America/Toronto')->toIso8601String(),
                'raw_markdown_hash' => null,
                'warning' => null,
            ], $partialAcquisition),
        ];
    }
}
