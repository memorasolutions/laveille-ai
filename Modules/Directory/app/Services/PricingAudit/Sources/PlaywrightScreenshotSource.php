<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Driver : capture screenshot pricing page via spatie/browsershot.
 * Poids 2 (browser-rendered = JS support + visual evidence audit-trail).
 * Stocke storage/app/pricing-evidence/{tool_id}-{timestamp}.png.
 *
 * S89 (#59) : guard config('directory.pricing_audit.screenshot_enabled') car Node
 * absent en prod cPanel partagé. Active via DIRECTORY_PRICING_AUDIT_SCREENSHOT=true
 * une fois Node + Chromium déployés. En attendant, retourne 'disabled' propre (pas
 * d'exception) pour ne pas polluer les batchs d'audit.
 */

namespace Modules\Directory\Services\PricingAudit\Sources;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\PricingAudit\PricingSourceResult;
use Spatie\Browsershot\Browsershot;

class PlaywrightScreenshotSource extends AbstractPricingSource
{
    public function name(): string
    {
        return 'screenshot';
    }

    public function weight(): int
    {
        return 2;
    }

    protected function doFetch(Tool $tool): PricingSourceResult
    {
        if (! config('directory.pricing_audit.screenshot_enabled', false)) {
            return new PricingSourceResult(
                sourceName: $this->name(),
                weight: $this->weight(),
                error: 'disabled (set DIRECTORY_PRICING_AUDIT_SCREENSHOT=true once Node prod ready)',
            );
        }

        if (empty($tool->url)) {
            return new PricingSourceResult($this->name(), $this->weight(), error: 'no url');
        }

        if (! class_exists(Browsershot::class)) {
            return new PricingSourceResult($this->name(), $this->weight(), error: 'browsershot not installed');
        }

        $url = $this->pricingUrl($tool->url);
        $timestamp = now()->format('Ymd_His');
        $relativePath = "pricing-evidence/{$tool->id}-{$timestamp}.png";
        $absolutePath = storage_path('app/' . $relativePath);

        // Crée le dossier au besoin
        @mkdir(dirname($absolutePath), 0755, true);

        $shotter = Browsershot::url($url)
            ->windowSize(1280, 800)
            ->fullPage()
            ->timeout(20)
            ->setOption('args', ['--no-sandbox']);

        // BROWSERSHOT_NODE_PATH dans .env prod (cf. handoff S86)
        if ($nodePath = config('services.browsershot.node_path')) {
            $shotter = $shotter->setNodeBinary($nodePath);
        }

        $shotter->save($absolutePath);

        // HTML hash pour audit-trail
        $html = '';
        try {
            $html = Browsershot::url($url)
                ->timeout(15)
                ->setOption('args', ['--no-sandbox'])
                ->bodyHtml();
        } catch (\Throwable $e) {
            // Non bloquant : screenshot OK suffit
        }

        $htmlHash = $html ? hash('sha256', $html) : null;
        $textPreview = $html ? Str::limit(strip_tags($html), 1500) : null;

        return new PricingSourceResult(
            sourceName: $this->name(),
            weight: $this->weight(),
            realPricing: null, // Pas d'analyse interprétative ici (laissée aux autres drivers)
            hasEducationDiscount: null,
            evidenceQuote: $textPreview ? Str::limit($textPreview, 240) : null,
            evidenceUrl: $url,
            confidence: 100, // Le screenshot est une preuve neutre, pas une opinion
            rawPayload: $textPreview,
            screenshotPath: $relativePath,
        );
    }

    private function pricingUrl(string $baseUrl): string
    {
        $parsed = parse_url($baseUrl);
        $root = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');

        return $root . '/pricing';
    }
}
