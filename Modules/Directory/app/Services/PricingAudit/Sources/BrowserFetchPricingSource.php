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
 *
 * S89 (#58) : User-Agent Chrome desktop réaliste + headers Accept/Accept-Language
 *             + 3 retries exponential backoff (250 / 750 / 2250 ms) sur 5xx/timeout
 *             + jitter inter-URLs pour réduire taux de blocage Cloudflare anti-bot.
 */

namespace Modules\Directory\Services\PricingAudit\Sources;

use Illuminate\Support\Facades\Http;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\PricingAudit\PricingSourceResult;
use Throwable;

class BrowserFetchPricingSource extends AbstractPricingSource
{
    /** @var string UA Chrome desktop réaliste (rotation possible mais 1 fixé suffit pour la majorité). */
    private const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    private const TIMEOUT_SEC = 12;

    private const MAX_ATTEMPTS = 3;

    /** Backoff par tentative (sleep en ms entre attempts). */
    private const BACKOFF_MS = [250, 750, 2250];

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
        $hit = null;
        $lastError = 'no candidate fetched';

        foreach ($candidateUrls as $i => $url) {
            // Jitter entre URLs (sauf la 1ère) pour éviter pattern bot-like rapide.
            if ($i > 0) {
                usleep(random_int(120_000, 380_000));
            }

            [$body, $err] = $this->fetchWithRetry($url);
            if ($body !== null) {
                $hit = ['url' => $url, 'body' => $body];
                break;
            }
            $lastError = $err;
        }

        if ($hit === null) {
            return new PricingSourceResult($this->name(), $this->weight(), error: $lastError);
        }

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
     * Fetch single URL avec 3 retries exponential backoff.
     * Retry sur : 5xx, 429, network exception. Pas de retry sur 4xx clients (sauf 429).
     *
     * @return array{0: ?string, 1: string} [body|null, error_message]
     */
    private function fetchWithRetry(string $url): array
    {
        $lastError = 'unknown';

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            if ($attempt > 0) {
                $delay = self::BACKOFF_MS[$attempt - 1] ?? 2250;
                usleep($delay * 1000);
            }

            try {
                $response = Http::timeout(self::TIMEOUT_SEC)
                    ->withHeaders([
                        'User-Agent' => self::USER_AGENT,
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                        'Accept-Language' => 'en-US,en;q=0.9,fr-CA;q=0.7,fr;q=0.5',
                        'Accept-Encoding' => 'gzip, deflate, br',
                        'Cache-Control' => 'no-cache',
                        'Pragma' => 'no-cache',
                        'Sec-Fetch-Dest' => 'document',
                        'Sec-Fetch-Mode' => 'navigate',
                        'Sec-Fetch-Site' => 'none',
                        'Sec-Fetch-User' => '?1',
                        'Upgrade-Insecure-Requests' => '1',
                    ])
                    ->withOptions(['allow_redirects' => ['max' => 5, 'strict' => false]])
                    ->get($url);

                $status = $response->status();

                if ($response->successful()) {
                    return [$response->body(), 'ok'];
                }

                // Retry seulement sur 429, 5xx
                if ($status === 429 || ($status >= 500 && $status < 600)) {
                    $lastError = "http {$status} retry";
                    continue;
                }

                // 4xx définitif (403 anti-bot, 404, etc.) -> pas de retry inutile
                return [null, "http {$status} blocked"];
            } catch (Throwable $e) {
                $lastError = 'exception: ' . substr($e->getMessage(), 0, 80);
                continue;
            }
        }

        return [null, $lastError];
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
