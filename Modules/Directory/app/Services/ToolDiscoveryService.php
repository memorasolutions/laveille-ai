<?php

declare(strict_types=1);

namespace Modules\Directory\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ToolNameCleanerService;

class ToolDiscoveryService
{
    private const TRACKING_PARAMS = [
        'ref', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'fbclid', 'gclid', 'msclkid', 'mc_cid', 'mc_eid',
        'app_id', // paramètre de suivi ProductHunt (survivait à cleanUrl(), incident 2026-08-22
    ];

    /** Nombre maximal de sauts suivis pour résoudre une redirection de suivi ProductHunt. */
    private const MAX_PRODUCTHUNT_HOPS = 3;

    /**
     * Bilan de la présente exécution du pipeline de découverte (correctif 2026-08-22 : sans ce
     * compteur, le bilan de fin de tools:discover-new ne peut pas distinguer un pipeline qui ne
     * trouve rien d'un pipeline qui trouve tout et le refuse). Alimenté par resolveProductHuntUrl()
     * pour les adresses ProductHunt non résolues, et par ingest() pour les 3 autres motifs de
     * refus. 'examined' n'est jamais compté séparément (getDiscoveryStats() le dérive) : il ne
     * peut donc jamais diverger de la somme accepté+refusés.
     */
    private array $discoveryStats = [
        'accepted' => 0,
        'refused' => [
            'adresse_non_resolue' => 0,
            'agregateur' => 0,
            'titre_commande' => 0,
            'doublon' => 0,
        ],
    ];

    /**
     * Compteurs chiffrés de la présente exécution, pour le bilan de fin de tools:discover-new
     * (DiscoverNewToolsCommand). 'examined' est dérivé (accepté + total des refus), jamais
     * compté à part, pour ne jamais pouvoir diverger de la somme de ses parties.
     */
    public function getDiscoveryStats(): array
    {
        $refusedTotal = array_sum($this->discoveryStats['refused']);

        return [
            'examined' => $this->discoveryStats['accepted'] + $refusedTotal,
            'accepted' => $this->discoveryStats['accepted'],
            'refused' => $this->discoveryStats['refused'],
            'refused_total' => $refusedTotal,
        ];
    }

    /**
     * Motif du DERNIER refus d'ingest() - 'agregateur', 'titre_commande' ou 'doublon' (les mêmes
     * clés que discoveryStats['refused'], jamais une deuxième taxonomie), ou null si le dernier
     * appel a accepté la fiche. Remis à null au tout début de CHAQUE appel à ingest() : ne peut
     * jamais refléter un appel antérieur.
     *
     * Correctif 2026-08-22 (finition 2) : DiscoverNewToolsCommand affichait « Doublon, ignoré. »
     * pour les trois motifs de refus, y compris agrégateur et titre-commande - trompeur pour qui
     * lance la commande à la main. ingest() reste `?Tool` (aucun appelant existant, y compris les
     * tests, n'a besoin d'être modifié) ; ce simple accesseur - même principe que
     * getDiscoveryStats() ci-dessus - est la façon la MOINS intrusive de porter le motif jusqu'à
     * l'appelant sans toucher la signature publique.
     */
    private ?string $lastRefusalReason = null;

    public function getLastRefusalReason(): ?string
    {
        return $this->lastRefusalReason;
    }

    public static function cleanUrl(string $url): string
    {
        $parts = parse_url($url);
        if (! $parts || empty($parts['host'])) {
            return $url;
        }

        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
            foreach (self::TRACKING_PARAMS as $key) {
                unset($query[$key]);
            }
        }

        $out = ($parts['scheme'] ?? 'https').'://'.$parts['host'];
        if (isset($parts['port'])) {
            $out .= ':'.$parts['port'];
        }
        $out .= $parts['path'] ?? '';

        if (! empty($query)) {
            $out .= '?'.http_build_query($query);
        }
        if (isset($parts['fragment'])) {
            $out .= '#'.$parts['fragment'];
        }

        return $out;
    }

    public static function isUrlExcluded(string $url): bool
    {
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['host'])) {
            return true;
        }

        $host = strtolower($parsed['host']);
        $path = isset($parsed['path']) ? strtolower($parsed['path']) : '';

        $blockedHosts = [
            // Pages de DÉCOUVERTE (agrégateurs) - jamais l'adresse officielle d'un produit.
            // ATTENTION : ne jamais y ajouter github.com ni huggingface.co, qui sont parfois
            // l'adresse officielle légitime d'un produit (ex. fiche MemoryCustodian → dépôt
            // GitHub). Voir correctif 2026-08-22 (défaut : fiches pointant vers la redirection
            // de suivi producthunt.com/r/p/... au lieu du site du produit).
            'producthunt.com',
            'news.ycombinator.com',
            'hn.algolia.com',
            'news.google.com',
            'reddit.com',
            'youtube.com',
            'youtu.be',
            'vimeo.com',
            'github.io',
            'framer.website',
            'vercel.app',
            'netlify.app',
            'notion.site',
            'medium.com',
            'substack.com',
            'dev.to',
            'hashnode.dev',
        ];

        foreach ($blockedHosts as $blockedHost) {
            if (str_contains($host, $blockedHost)) {
                return true;
            }
        }

        $blockedPathPatterns = [
            '/blog/',
            '/article/',
            '/articles/',
            '/papers/',
            '/research/',
            '/watch',
            '/vibes/',
            '/post/',
            '/posts/',
        ];

        foreach ($blockedPathPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public function discoverAll(): array
    {
        $allTools = [];

        try {
            $tools = $this->fetchProductHunt();
            $allTools = array_merge($allTools, $tools);
            Log::info('[ToolDiscovery] ProductHunt', ['count' => count($tools)]);
        } catch (\Exception $e) {
            Log::error('[ToolDiscovery] ProductHunt échoué', ['error' => $e->getMessage()]);
        }

        try {
            $tools = $this->fetchRssFeeds();
            $allTools = array_merge($allTools, $tools);
            Log::info('[ToolDiscovery] RSS feeds', ['count' => count($tools)]);
        } catch (\Exception $e) {
            Log::error('[ToolDiscovery] RSS feeds échoué', ['error' => $e->getMessage()]);
        }

        // Ne garder que les outils tech pertinents
        return $this->filterTechRelevant($allTools);
    }

    public function fetchProductHunt(): array
    {
        $token = config('directory.producthunt_token');
        if (! $token) {
            // Canal dédié (pas le canal par défaut, avalé par LOG_LEVEL=error en production) :
            // cet avertissement est LE PLUS IMPORTANT des cinq du pipeline. C'est l'absence de
            // jeton qui fait basculer la découverte sur la seule voie RSS, celle qui produisait
            // les mauvaises adresses (défaut 2026-08-22). Si ce message reste invisible, rien
            // n'explique pourquoi la voie ProductHunt n'est pas utilisée.
            Log::channel('directory_discovery')->warning('[ToolDiscovery] ProductHunt token non configuré, source ignorée.');

            return [];
        }

        $query = <<<'GRAPHQL'
        query {
            posts(first: 20, topic: "artificial-intelligence", order: NEWEST) {
                edges {
                    node {
                        name
                        tagline
                        website
                        pricingType
                    }
                }
            }
        }
        GRAPHQL;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://api.producthunt.com/v2/api/graphql', [
            'query' => $query,
        ]);

        if (! $response->successful()) {
            Log::channel('directory_discovery')->warning('[ToolDiscovery] ProductHunt API erreur', ['status' => $response->status()]);

            return [];
        }

        $data = $response->json();
        $tools = [];

        foreach ($data['data']['posts']['edges'] ?? [] as $edge) {
            $node = $edge['node'] ?? [];
            if (empty($node['name']) || empty($node['website'])) {
                continue;
            }
            $tools[] = [
                'name' => $node['name'],
                'url' => self::cleanUrl($node['website']),
                'description' => $node['tagline'] ?? '',
                'pricing' => $this->mapPricing($node['pricingType'] ?? ''),
                'source' => 'producthunt',
            ];
        }

        return $tools;
    }

    public function fetchRssFeeds(): array
    {
        $feeds = config('directory.discovery_feeds', []);
        if (empty($feeds)) {
            return [];
        }

        $tools = [];

        foreach ($feeds as $feedName => $feedUrl) {
            try {
                $response = Http::timeout(20)->get($feedUrl);
                if (! $response->successful()) {
                    continue;
                }

                $xml = @simplexml_load_string($response->body());
                if ($xml === false) {
                    Log::channel('directory_discovery')->warning('[ToolDiscovery] RSS XML invalide', ['feed' => $feedName]);

                    continue;
                }

                // Support RSS 2.0 (channel/item) et Atom (entry)
                $isAtom = isset($xml->entry);
                $items = $isAtom ? $xml->entry : ($xml->channel->item ?? []);

                foreach ($items as $item) {
                    $title = (string) ($item->title ?? '');

                    if ($isAtom) {
                        // Atom : lien dans attribut href
                        $link = '';
                        foreach ($item->link as $atomLink) {
                            if ((string) $atomLink['rel'] === 'alternate') {
                                $link = (string) $atomLink['href'];
                                break;
                            }
                        }
                        if (! $link && isset($item->link['href'])) {
                            $link = (string) $item->link['href'];
                        }
                        // Extraire description propre (1er paragraphe = tagline)
                        $content = (string) ($item->content ?? '');
                        $desc = strip_tags($content);
                        // Retirer les artefacts de navigation PH
                        $desc = preg_replace('/\s*Discussion\s*\|\s*Link\s*/i', '', $desc);
                    } else {
                        // RSS 2.0
                        $link = (string) ($item->link ?? '');
                        $desc = (string) ($item->description ?? '');
                        $desc = strip_tags($desc);
                    }

                    if (empty($title) || empty($link)) {
                        continue;
                    }

                    $desc = Str::limit(trim($desc), 500);

                    // Extraire le vrai lien produit depuis le contenu PH (/r/p/ID)
                    $realUrl = $link;
                    if ($isAtom && str_contains($link, 'producthunt.com') && preg_match('/href="(https:\/\/www\.producthunt\.com\/r\/[^"]+)"/', $content, $rMatch)) {
                        $resolved = $this->resolveProductHuntUrl($rMatch[1]);

                        if ($resolved === null) {
                            // Résolution impossible (déjà journalisée ET comptabilisée par
                            // resolveProductHuntUrl() sur le canal 'directory_discovery') : on
                            // ignore cette découverte plutôt que d'enregistrer l'URL de suivi.
                            continue;
                        }

                        $realUrl = $resolved;
                    }

                    $tools[] = [
                        'name' => $title,
                        'url' => self::cleanUrl($realUrl),
                        'description' => $desc,
                        'pricing' => 'freemium',
                        'source' => "rss:{$feedName}",
                    ];
                }
            } catch (\Exception $e) {
                Log::channel('directory_discovery')->warning('[ToolDiscovery] RSS feed échoué', ['feed' => $feedName, 'error' => $e->getMessage()]);
            }
        }

        return $tools;
    }

    public function ingest(array $toolData): ?Tool
    {
        // Chaque appel repart de zéro : jamais le motif d'un appel précédent.
        $this->lastRefusalReason = null;

        $url = $toolData['url'] ?? null;
        $name = $toolData['name'] ?? null;

        if (! $url || ! $name) {
            return null;
        }

        $name = ToolNameCleanerService::clean($name);

        if ($name === '') {
            return null;
        }

        if (ToolNameCleanerService::looksLikeShellCommand($name)) {
            $this->discoveryStats['refused']['titre_commande']++;
            $this->lastRefusalReason = 'titre_commande';

            Log::channel('directory_discovery')->warning('[ToolDiscovery] Fiche refusée : titre ressemble à une commande shell', [
                'name' => $name,
                'url' => $url,
            ]);

            return null;
        }

        $url = self::cleanUrl($url);

        if (self::isUrlExcluded($url)) {
            $this->discoveryStats['refused']['agregateur']++;
            $this->lastRefusalReason = 'agregateur';

            Log::channel('directory_discovery')->warning('[ToolDiscovery] Fiche refusée : adresse de découverte/agrégateur', [
                'name' => $name,
                'url' => $url,
            ]);

            return null;
        }

        // Dédup par domaine (sauf plateformes d'agrégation)
        $host = parse_url($url, PHP_URL_HOST);
        $host = preg_replace('/^www\./', '', $host ?? '');

        $platformDomains = ['producthunt.com', 'alternativeto.net', 'g2.com', 'capterra.com'];
        $isPlatform = false;
        foreach ($platformDomains as $pd) {
            if ($host && str_contains($host, $pd)) {
                $isPlatform = true;
                break;
            }
        }

        if ($host && ! $isPlatform && Tool::where('url', 'LIKE', "%{$host}%")->exists()) {
            $this->discoveryStats['refused']['doublon']++;
            $this->lastRefusalReason = 'doublon';

            return null;
        }

        // Dédup par URL exacte (pour les plateformes)
        if ($isPlatform && Tool::where('url', $url)->exists()) {
            $this->discoveryStats['refused']['doublon']++;
            $this->lastRefusalReason = 'doublon';

            return null;
        }

        // Dédup par nom : égalité STRICTE après normalisation (aliases inclus) en plus du score
        // flou existant ci-dessous - correctif mesuré ticket #2175 (2026-09-02). Le score flou
        // seul manquait de façon systématique les 5 doublons réels du catalogue (Voiser AI,
        // CaseGap AI, Thinnest AI, NoMac.app, Animos App - tous créés par CE pipeline, le dernier
        // le 15 juillet 2026) : leur nom était pourtant identique caractère pour caractère d'une
        // fiche à l'autre, mais le suffixe générique (« AI »/« App »/« Tool ») retiré ci-dessous
        // n'est retiré QUE du candidat entrant, jamais du nom déjà en base - asymétrie qui
        // abaissait le score sous le seuil de 85 (75-84 % mesurés, jamais 100 %).
        //
        // Volontairement fondé sur le NOM SEUL, jamais sur l'URL en plus : la mesure a montré que
        // les 5 doublons réels n'ont JAMAIS le même domaine (site officiel vs page ProductHunt ou
        // domaine miroir - c'est précisément pourquoi ils existent), donc une garde qui exigerait
        // AUSSI l'égalité d'URL n'en aurait bloqué AUCUN. Vérifié sans risque nouveau pour les
        // familles de produits légitimes qui partagent un domaine sous des noms différents (ex.
        // Stability AI / Stable Diffusion, ElevenLabs / ElevenAgents Guardrails 2.0 - aucun des
        // deux ne partage de nom normalisé, donc ni l'un ni l'autre n'est jamais concerné).
        $nameNormExact = ToolNameCleanerService::normalizeForComparison($name);

        // Dédup par nom fuzzy (aliases inclus) - contrôle existant, seuil et comportement inchangés.
        $nameNorm = preg_replace('/\s*(ai|tool|app)\s*$/i', '', $name);
        $existing = Tool::select('id', 'name', 'aliases')->get();

        foreach ($existing as $tool) {
            if ($tool->matchesNameExact($nameNormExact) || $tool->matchesName($nameNorm) > 85) {
                $this->discoveryStats['refused']['doublon']++;
                $this->lastRefusalReason = 'doublon';

                return null;
            }
        }

        // Slug unique
        $locale = 'fr_CA';
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Tool::where("slug->{$locale}", $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        // Créer l'outil en pending
        $tool = new Tool;
        $tool->setTranslation('name', $locale, $name);
        $tool->setTranslation('slug', $locale, $slug);
        $tool->setTranslation('description', $locale, $toolData['description'] ?? '');
        $tool->setTranslation('short_description', $locale, Str::limit($toolData['description'] ?? '', 200));
        $tool->url = $url;
        $tool->pricing = $this->mapPricing($toolData['pricing'] ?? 'freemium');
        $tool->status = 'pending';
        $tool->is_featured = false;
        $tool->clicks_count = 0;
        $tool->sort_order = 0;
        $tool->submitted_by = null;
        if (\Illuminate\Support\Facades\Schema::hasColumn('directory_tools', 'metadata')) {
            $tool->metadata = [
                'source' => $toolData['source'] ?? 'unknown',
                'discovered_at' => now()->toIso8601String(),
            ];
        }
        $tool->save();

        $this->discoveryStats['accepted']++;

        Log::info('[ToolDiscovery] Outil ingéré', [
            'id' => $tool->id,
            'name' => $name,
            'url' => $url,
            'source' => $toolData['source'] ?? 'unknown',
        ]);

        return $tool;
    }

    /**
     * Filtre les outils pour ne garder que ceux pertinents au répertoire techno.
     */
    public function filterTechRelevant(array $tools): array
    {
        $keywords = [
            // IA / ML / LLM
            'ai', 'artificial intelligence', 'machine learning', 'deep learning', 'llm', 'gpt',
            'chatbot', 'copilot', 'neural', 'nlp', 'generative', 'prompt', 'agent', 'rag',
            'computer vision', 'speech', 'transcription', 'diffusion', 'text-to-image',
            'intelligence artificielle', 'apprentissage automatique',
            // Développeurs / IDE / API
            'developer', 'développeur', 'ide', 'ci/cd', 'github', 'gitlab', 'sdk', 'api',
            'graphql', 'testing', 'playwright', 'debugging', 'code review', 'cli',
            'docker', 'framework', 'open source', 'open-source',
            // SaaS / Productivité
            'saas', 'productivity', 'productivité', 'collaboration', 'project management',
            'gestion de projet', 'dashboard', 'tableau de bord', 'documentation',
            // Design / No-code
            'design', 'figma', 'no-code', 'nocode', 'low-code', 'lowcode', 'prototype',
            'website builder', 'drag-and-drop',
            // Cybersécurité
            'cybersecurity', 'cybersécurité', 'security', 'sécurité', 'encryption', 'vpn',
            'authentication', 'zero trust', 'vulnerability',
            // Cloud / DevOps
            'cloud', 'aws', 'azure', 'gcp', 'serverless', 'kubernetes', 'terraform',
            'devops', 'monitoring', 'deployment', 'déploiement',
            // Analytics / SEO / Marketing
            'analytics', 'seo', 'référencement', 'marketing digital', 'crm', 'email marketing',
            'conversion', 'tracking',
            // Automatisation
            'automation', 'automatisation', 'workflow', 'rpa', 'integration', 'scraping', 'etl',
            // Éducation tech
            'e-learning', 'bootcamp', 'coding', 'programmation', 'tutorial', 'tutoriel',
            // Data
            'database', 'base de données', 'sql', 'data science', 'big data',
            // Termes généraux tech
            'startup', 'tech', 'software', 'logiciel', 'plugin', 'extension', 'outil', 'tool',
            'blockchain', 'web3', 'iot', 'ar', 'vr', '3d', 'robotics', 'quantum',
        ];

        return array_values(array_filter($tools, function ($tool) use ($keywords) {
            $text = mb_strtolower(($tool['name'] ?? '') . ' ' . ($tool['description'] ?? ''));
            foreach ($keywords as $kw) {
                if (str_contains($text, $kw)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * Résout une redirection de suivi ProductHunt (/r/p/ID) vers l'adresse réelle du produit.
     *
     * Boucle sur au maximum self::MAX_PRODUCTHUNT_HOPS sauts (une chaîne de redirection peut
     * rester plusieurs fois sur producthunt.com avant d'atteindre le site du produit). Échec
     * BRUYANT et volontaire : si l'hôte final est toujours producthunt.com après épuisement des
     * sauts, si un saut ne renvoie aucun en-tête Location, ou si une exception survient, on
     * journalise la raison précise et on retourne null - jamais l'URL de suivi elle-même. Un
     * repli silencieux sur $phUrl est précisément le défaut corrigé le 2026-08-22 (21 fiches
     * d'annuaire pointaient vers producthunt.com au lieu du site du produit).
     */
    public function resolveProductHuntUrl(string $phUrl): ?string
    {
        if (! str_contains($phUrl, 'producthunt.com')) {
            return $phUrl;
        }

        $current = $phUrl;

        try {
            for ($hop = 1; $hop <= self::MAX_PRODUCTHUNT_HOPS; $hop++) {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/125.0.0.0',
                ])->withOptions(['allow_redirects' => false])->timeout(10)->get($current);

                $location = $response->header('Location');

                if (! $location) {
                    $this->discoveryStats['refused']['adresse_non_resolue']++;

                    Log::channel('directory_discovery')->warning('[ToolDiscovery] Résolution ProductHunt échouée : aucun en-tête Location', [
                        'url_depart' => $phUrl,
                        'url_courante' => $current,
                        'saut' => $hop,
                    ]);

                    return null;
                }

                if (! str_contains($location, 'producthunt.com')) {
                    return $location;
                }

                $current = $location;
            }
        } catch (\Throwable $e) {
            $this->discoveryStats['refused']['adresse_non_resolue']++;

            Log::channel('directory_discovery')->warning('[ToolDiscovery] Résolution ProductHunt échouée : exception réseau', [
                'url_depart' => $phUrl,
                'url_courante' => $current,
                'erreur' => $e->getMessage(),
            ]);

            return null;
        }

        $this->discoveryStats['refused']['adresse_non_resolue']++;

        Log::channel('directory_discovery')->warning('[ToolDiscovery] Résolution ProductHunt échouée : toujours sur producthunt.com après le nombre maximal de sauts', [
            'url_depart' => $phUrl,
            'url_finale' => $current,
            'sauts' => self::MAX_PRODUCTHUNT_HOPS,
        ]);

        return null;
    }

    public function mapPricing(string $raw): string
    {
        $raw = strtolower(trim($raw));

        $mapping = [
            'free + paid' => 'freemium',
            'open source' => 'open_source',
            'open_source' => 'open_source',
            'opensource' => 'open_source',
            'enterprise' => 'enterprise',
            'freemium' => 'freemium',
            'premium' => 'paid',
            'paid' => 'paid',
            'free' => 'free',
        ];

        foreach ($mapping as $key => $value) {
            if (str_contains($raw, $key)) {
                return $value;
            }
        }

        return 'freemium';
    }
}
