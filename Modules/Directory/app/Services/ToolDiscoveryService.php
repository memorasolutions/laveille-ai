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
    ];

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
            Log::warning('[ToolDiscovery] ProductHunt token non configuré, source ignorée.');

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
            Log::warning('[ToolDiscovery] ProductHunt API erreur', ['status' => $response->status()]);

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
                    Log::warning('[ToolDiscovery] RSS XML invalide', ['feed' => $feedName]);

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
                        $realUrl = $this->resolveProductHuntUrl($rMatch[1]);
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
                Log::warning('[ToolDiscovery] RSS feed échoué', ['feed' => $feedName, 'error' => $e->getMessage()]);
            }
        }

        return $tools;
    }

    public function ingest(array $toolData): ?Tool
    {
        $url = $toolData['url'] ?? null;
        $name = $toolData['name'] ?? null;

        if (! $url || ! $name) {
            return null;
        }

        $name = ToolNameCleanerService::clean($name);

        if ($name === '') {
            return null;
        }

        $url = self::cleanUrl($url);

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
            return null;
        }

        // Dédup par URL exacte (pour les plateformes)
        if ($isPlatform && Tool::where('url', $url)->exists()) {
            return null;
        }

        // Dédup par nom fuzzy (aliases inclus)
        $nameNorm = preg_replace('/\s*(ai|tool|app)\s*$/i', '', $name);
        $existing = Tool::select('id', 'name', 'aliases')->get();

        foreach ($existing as $tool) {
            if ($tool->matchesName($nameNorm) > 85) {
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

    public function resolveProductHuntUrl(string $phUrl): string
    {
        if (! str_contains($phUrl, 'producthunt.com')) {
            return $phUrl;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/125.0.0.0',
            ])->withOptions(['allow_redirects' => false])->timeout(10)->get($phUrl);

            $location = $response->header('Location');
            if ($location && ! str_contains($location, 'producthunt.com')) {
                return $location;
            }
        } catch (\Throwable) {
        }

        return $phUrl;
    }

    public function mapPricing(string $raw): string
    {
        $raw = strtolower(trim($raw));

        $mapping = [
            'free' => 'free',
            'open source' => 'open_source',
            'open_source' => 'open_source',
            'opensource' => 'open_source',
            'paid' => 'paid',
            'premium' => 'paid',
            'freemium' => 'freemium',
            'free + paid' => 'freemium',
            'enterprise' => 'enterprise',
        ];

        foreach ($mapping as $key => $value) {
            if (str_contains($raw, $key)) {
                return $value;
            }
        }

        return 'freemium';
    }
}
