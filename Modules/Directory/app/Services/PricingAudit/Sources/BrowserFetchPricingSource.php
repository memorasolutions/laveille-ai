<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Driver : fetch HTTP simple (Laravel Http) + parse keywords pricing.
 * Poids 2 (vendor first-party text content).
 * Limites : ne capte pas le JS-loaded pricing -> à compléter avec PlaywrightScreenshotSource.
 */

namespace Modules\Directory\Services\PricingAudit\Sources;

use Illuminate\Support\Facades\Http;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\PricingAudit\PricingSourceResult;

class BrowserFetchPricingSource extends AbstractPricingSource
{
    public function name(): string
    {
        return 'browser';
    }

    public function weight(): int
    {
        return 2;
    }

    protected function doFetch(Tool $tool): PricingSourceResult
    {
        if (empty($tool->url)) {
            return new PricingSourceResult($this->name(), $this->weight(), error: 'no url');
        }

        $candidateUrls = $this->candidatePricingUrls($tool->url);
        $hits = [];

        foreach ($candidateUrls as $url) {
            $response = Http::timeout(8)->withUserAgent('Mozilla/5.0 (compatible; LaVeilleAuditor/1.0)')->get($url);
            if (! $response->successful()) {
                continue;
            }
            $body = $response->body();
            $hits[] = ['url' => $url, 'body' => $body];
            break; // 1ère URL réussie suffit
        }

        if (empty($hits)) {
            return new PricingSourceResult($this->name(), $this->weight(), error: 'fetch failed');
        }

        $hit = $hits[0];
        $signals = $this->extractSignals($hit['body']);

        return new PricingSourceResult(
            sourceName: $this->name(),
            weight: $this->weight(),
            realPricing: $signals['pricing'],
            hasEducationDiscount: $signals['has_edu'],
            educationUrl: $signals['edu_url'],
            evidenceQuote: $signals['quote'],
            evidenceUrl: $hit['url'],
            confidence: $signals['confidence'],
            rawPayload: substr($hit['body'], 0, 3000),
        );
    }

    /**
     * Génère les URLs candidates où trouver le pricing.
     */
    private function candidatePricingUrls(string $baseUrl): array
    {
        $base = rtrim($baseUrl, '/');
        $parsed = parse_url($baseUrl);
        $root = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');

        return array_unique([
            $base . '/pricing',
            $root . '/pricing',
            $base . '/price',
            $root . '/plans',
            $base,
        ]);
    }

    /**
     * Extrait les signaux pricing du HTML brut (regex tolérante).
     */
    private function extractSignals(string $html): array
    {
        $text = strtolower(strip_tags($html));
        $hasFreeTrial = preg_match('/(free\s+trial|essai\s+gratuit|trial)/i', $text);
        $hasFree = preg_match('/(\b free\b|\bgratuit\b|forever free|no\s+credit\s+card)/i', $text);
        $hasFreemium = preg_match('/(freemium|free\s+plan)/i', $text);
        $hasPaid = preg_match('/(\$\d+|\d+\s?\$|\d+\s?€|\d+\s?USD|month|monthly|per\s+seat|per\s+user|paid)/i', $text);
        $hasEnterprise = preg_match('/(enterprise|contact\s+sales|custom\s+pricing)/i', $text);
        $hasOpenSource = preg_match('/(open\s+source|MIT\s+license|apache\s+license|github\.com.*\/blob)/i', $text);
        $hasEdu = preg_match('/(education(al)?\s+(discount|pricing|plan)|student\s+(discount|pricing)|academic\s+(discount|pricing))/i', $text, $eduMatch);

        $pricing = match (true) {
            (bool) $hasOpenSource => 'open_source',
            (bool) $hasFree && ! $hasPaid && ! $hasFreemium => 'free',
            (bool) $hasFreemium || ((bool) $hasFree && (bool) $hasPaid) => 'freemium',
            (bool) $hasFreeTrial && ! $hasFreemium => 'free_trial',
            (bool) $hasEnterprise && ! $hasPaid => 'enterprise',
            (bool) $hasPaid => 'paid',
            default => null,
        };

        $confidence = $pricing ? 60 : 30;
        if ($pricing && ($hasFree && $hasPaid && $hasFreemium)) {
            $confidence = 75; // Triple-keyword match = freemium very likely
        }

        $eduQuote = $eduMatch[0] ?? null;
        $quote = $eduQuote ?? ($pricing ? "Detected pattern: {$pricing}" : 'No pricing pattern matched');

        return [
            'pricing' => $pricing,
            'has_edu' => (bool) $hasEdu,
            'edu_url' => null, // À enrichir éventuellement par scan link rel
            'quote' => substr($quote, 0, 240),
            'confidence' => $confidence,
        ];
    }
}
