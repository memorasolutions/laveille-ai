<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolPricingAudit;
use Modules\Directory\Services\PricingAudit\PricingAuditScheduler;
use Modules\Directory\Services\PricingAudit\PricingSourceResult;
use Modules\Directory\Services\PricingAudit\Sources\AbstractPricingSource;
use Modules\Directory\Services\PricingAudit\Sources\BrowserFetchPricingSource;
use Modules\Directory\Services\PricingAudit\Sources\PlaywrightScreenshotSource;
use Modules\Directory\Services\PricingAudit\Sources\PpSearchPricingSource;
use Modules\Directory\Services\PricingAudit\ToolPricingAuditor;

// Stub source pour tests sans HTTP/IO réel
class StubSource extends AbstractPricingSource
{
    public function __construct(
        private string $sourceName,
        private int $weight,
        private ?PricingSourceResult $resultOverride = null,
    ) {}

    public function name(): string { return $this->sourceName; }
    public function weight(): int { return $this->weight; }

    protected function doFetch(Tool $tool): PricingSourceResult
    {
        return $this->resultOverride ?? new PricingSourceResult($this->sourceName, $this->weight, error: 'no override');
    }
}

it('PricingSourceResult marks valid only with realPricing and no error', function () {
    $valid = new PricingSourceResult('test', 1, realPricing: 'free', confidence: 80);
    $invalid = new PricingSourceResult('test', 1, error: 'fail');
    $invalid2 = new PricingSourceResult('test', 1);

    expect($valid->isValid())->toBeTrue();
    expect($invalid->isValid())->toBeFalse();
    expect($invalid2->isValid())->toBeFalse();
});

it('runConsensus elects pricing with highest weighted vote', function () {
    $auditor = new ToolPricingAuditor();
    $results = collect([
        new PricingSourceResult('a', 2, realPricing: 'freemium', confidence: 80),
        new PricingSourceResult('b', 1, realPricing: 'freemium', confidence: 70),
        new PricingSourceResult('c', 1, realPricing: 'paid', confidence: 60),
    ]);

    $consensus = $auditor->runConsensus($results);

    expect($consensus['real_pricing'])->toBe('freemium'); // poids 3 vs 1
    expect($consensus['confidence'])->toBeGreaterThan(0);
});

it('runConsensus returns null when all sources errored', function () {
    $auditor = new ToolPricingAuditor();
    $results = collect([
        new PricingSourceResult('a', 1, error: 'fail'),
        new PricingSourceResult('b', 1, error: 'fail'),
    ]);

    $consensus = $auditor->runConsensus($results);

    expect($consensus['real_pricing'])->toBeNull();
    expect($consensus['weighted_score'])->toBe(0);
    expect($consensus['confidence'])->toBe(0);
});

it('runConsensus aggregates education discount by majority weight', function () {
    $auditor = new ToolPricingAuditor();
    $results = collect([
        new PricingSourceResult('a', 2, realPricing: 'paid', hasEducationDiscount: true, confidence: 80),
        new PricingSourceResult('b', 1, realPricing: 'paid', hasEducationDiscount: false, confidence: 70),
    ]);

    $consensus = $auditor->runConsensus($results);

    expect($consensus['has_education_discount'])->toBeTrue(); // weight 2 vs 1
});

it('PricingAuditScheduler returns correct SLA per tier', function () {
    $scheduler = new PricingAuditScheduler();

    expect($scheduler->slaForTier('top100'))->toBe(30);
    expect($scheduler->slaForTier('mid'))->toBe(60);
    expect($scheduler->slaForTier('longtail'))->toBe(90);
});

it('AbstractPricingSource normalizes pricing strings', function () {
    $source = new class extends AbstractPricingSource {
        public function name(): string { return 'test'; }
        public function weight(): int { return 1; }
        protected function doFetch(Tool $t): PricingSourceResult { return new PricingSourceResult('test', 1); }
        public function testNorm(?string $raw): ?string { return $this->normalizePricing($raw); }
    };

    expect($source->testNorm('Free trial'))->toBe('free_trial');
    expect($source->testNorm('open-source'))->toBe('open_source');
    expect($source->testNorm('Freemium'))->toBe('freemium');
    expect($source->testNorm('Gratuit'))->toBe('free');
    expect($source->testNorm(null))->toBeNull();
});

it('PricingSourceResult toArray serializes correctly', function () {
    $r = new PricingSourceResult(
        sourceName: 'pp',
        weight: 1,
        realPricing: 'freemium',
        hasEducationDiscount: true,
        evidenceQuote: 'Free + Pro at $20/mo',
        confidence: 85,
    );

    $arr = $r->toArray();

    expect($arr)->toHaveKeys(['source', 'weight', 'real_pricing', 'has_education_discount', 'confidence']);
    expect($arr['source'])->toBe('pp');
    expect($arr['real_pricing'])->toBe('freemium');
});

it('compare-bar still has v1.3.0 onboarding (regression check)', function () {
    $rendered = view('directory::components.compare-bar')->render();
    expect($rendered)->toContain('lv-cmp-onboarding');
    expect($rendered)->toContain('lv-cmp-popover');
});

it('BrowserFetchPricingSource sends a real Chrome UA + headers (S89 #58)', function () {
    Http::fake([
        '*' => Http::response('<html><body>Free plan and $20/mo</body></html>', 200, ['Content-Type' => 'text/html']),
    ]);

    $source = new BrowserFetchPricingSource();
    $tool = new Tool(['url' => 'https://example.com', 'name' => 'Example']);
    $tool->id = 999_999;

    $result = $source->fetch($tool);

    expect($result->isValid())->toBeTrue();
    expect($result->realPricing)->toBe('freemium');

    Http::assertSent(function ($request) {
        $ua = $request->header('User-Agent')[0] ?? '';
        $accept = $request->header('Accept')[0] ?? '';
        return str_contains($ua, 'Chrome/') && str_contains($accept, 'text/html');
    });
});

it('BrowserFetchPricingSource retries 3x on 503 and gives up cleanly (S89 #58)', function () {
    Http::fake([
        '*' => Http::response('upstream', 503),
    ]);

    $source = new BrowserFetchPricingSource();
    $tool = new Tool(['url' => 'https://example.com', 'name' => 'Example']);
    $tool->id = 999_998;

    $result = $source->fetch($tool);

    expect($result->isValid())->toBeFalse();
    expect($result->error)->toContain('http 503');
});

it('BrowserFetchPricingSource gives up immediately on 403 (no retry on hard block)', function () {
    Http::fake([
        '*' => Http::response('blocked', 403),
    ]);

    $source = new BrowserFetchPricingSource();
    $tool = new Tool(['url' => 'https://example.com', 'name' => 'Example']);
    $tool->id = 999_997;

    $result = $source->fetch($tool);

    expect($result->isValid())->toBeFalse();
    expect($result->error)->toContain('http 403 blocked');
});

it('PpSearchPricingSource direct API parses a clean JSON answer (S89 #60)', function () {
    config(['directory.openrouter_api_key' => 'fake-test-key']);

    Http::fake([
        'openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'real_pricing' => 'freemium',
                            'has_education_discount' => true,
                            'education_url' => 'https://example.com/edu',
                            'evidence_quote' => 'Free for students, Pro from $20/mo',
                            'evidence_url' => 'https://example.com/pricing',
                            'confidence' => 92,
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    $source = new PpSearchPricingSource();
    $tool = new Tool(['url' => 'https://example.com', 'name' => 'Example']);
    $tool->id = 990_001;

    $result = $source->fetch($tool);

    expect($result->isValid())->toBeTrue();
    expect($result->realPricing)->toBe('freemium');
    expect($result->hasEducationDiscount)->toBeTrue();
    expect($result->educationUrl)->toBe('https://example.com/edu');
    expect($result->confidence)->toBe(92);
});

it('PpSearchPricingSource direct API extracts JSON from markdown fences (S89 #60)', function () {
    config(['directory.openrouter_api_key' => 'fake-test-key']);

    $fenced = "```json\n" . json_encode([
        'real_pricing' => 'paid',
        'has_education_discount' => false,
        'evidence_quote' => 'Starts at $15/mo',
        'evidence_url' => 'https://example.com',
        'confidence' => 80,
    ]) . "\n```";

    Http::fake([
        'openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => $fenced]]],
        ], 200),
    ]);

    $source = new PpSearchPricingSource();
    $tool = new Tool(['url' => 'https://example.com', 'name' => 'Example']);
    $tool->id = 990_002;

    $result = $source->fetch($tool);

    expect($result->isValid())->toBeTrue();
    expect($result->realPricing)->toBe('paid');
    expect($result->hasEducationDiscount)->toBeFalse();
});

it('PlaywrightScreenshotSource is disabled by default (S89 #59 — guards Node-less prod)', function () {
    config(['directory.pricing_audit.screenshot_enabled' => false]);

    $source = new PlaywrightScreenshotSource();
    $tool = new Tool(['url' => 'https://example.com', 'name' => 'Example']);
    $tool->id = 990_010;

    $result = $source->fetch($tool);

    expect($result->isValid())->toBeFalse();
    expect($result->error)->toContain('disabled');
});

it('PpSearchPricingSource precomputed mode still works (backward compat S89 #60)', function () {
    $source = new PpSearchPricingSource();
    $source->setPrecomputed([
        12345 => [
            'real_pricing' => 'free_trial',
            'has_education_discount' => false,
            'evidence_quote' => 'Pre-computed snapshot',
            'evidence_url' => 'https://example.com',
            'confidence' => 75,
        ],
    ]);

    $tool = new Tool(['url' => 'https://example.com', 'name' => 'Example']);
    $tool->id = 12345;

    $result = $source->fetch($tool);

    expect($result->isValid())->toBeTrue();
    expect($result->realPricing)->toBe('free_trial');
    expect($result->confidence)->toBe(75);
});
