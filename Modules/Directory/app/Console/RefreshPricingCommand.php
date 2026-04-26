<?php declare(strict_types=1);

namespace Modules\Directory\Console;

use App\Console\Concerns\HasKillSwitch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\OpenRouterService;
use Modules\Directory\Support\PricingCategories;
use Throwable;

class RefreshPricingCommand extends Command
{
    use HasKillSwitch;

    protected $signature = 'directory:refresh-pricing {--batch=5} {--reset-suspects}';
    protected $description = 'Refresh pricing categories using LLM structured output';

    public function handle(OpenRouterService $openRouterService): int
    {
        if ($this->shouldSkipForKillSwitch('cron.directory-pricing')) {
            return self::SUCCESS;
        }

        $batch = (int) $this->option('batch');
        $resetSuspects = (bool) $this->option('reset-suspects');

        if ($resetSuspects) {
            $tools = Tool::published()->notArchived()
                ->where('last_enriched_at', '>=', '2026-04-15')
                ->get();
        } else {
            $tools = Tool::published()->notArchived()
                ->orderByRaw("FIELD(pricing, 'freemium', 'free_trial', 'paid', 'free', 'open_source', 'enterprise')")
                ->orderBy('updated_at')
                ->limit($batch)
                ->get();
        }

        $verified = 0;
        $modified = 0;
        $errors = 0;
        $lowConfidence = 0;

        foreach ($tools as $tool) {
            if ($this->shouldSkipForKillSwitch('cron.directory-pricing')) {
                break;
            }

            $verified++;
            $toolName = $tool->getTranslation('name', 'fr_CA') ?: $tool->name;

            $prompt = $this->buildPrompt($toolName, $tool->url ?? '');
            $response = $openRouterService->classifyPricing($prompt);

            try {
                $jsonStart = strpos($response, '{');
                $jsonEnd = strrpos($response, '}');
                if ($jsonStart === false || $jsonEnd === false) {
                    throw new \JsonException('No JSON object found in response');
                }
                $json = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
                $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

                if (!is_array($data)) {
                    throw new \JsonException('Invalid JSON structure');
                }

                $requiredKeys = ['category', 'confidence', 'evidence'];
                foreach ($requiredKeys as $key) {
                    if (!array_key_exists($key, $data)) {
                        throw new \JsonException("Missing key: {$key}");
                    }
                }

                $category = $data['category'];
                $confidence = (float) $data['confidence'];
                $evidence = (string) $data['evidence'];

                if (!in_array($category, PricingCategories::values(), true)) {
                    throw new \JsonException("Invalid category: {$category}");
                }

                if ($confidence < 0.6) {
                    Log::info("Low confidence for tool {$tool->id}: {$confidence}", [
                        'tool_name' => $toolName,
                        'category' => $category,
                        'evidence' => $evidence,
                    ]);

                    \Modules\Directory\Models\ToolPricingReport::create([
                        'tool_id' => $tool->id,
                        'user_id' => null,
                        'reported_pricing' => $category,
                        'current_pricing_snapshot' => $tool->pricing,
                        'evidence_url' => null,
                        'user_notes' => 'Auto-flagged low confidence: ' . $confidence,
                        'status' => 'pending',
                        'admin_notes' => 'Evidence: ' . $evidence,
                    ]);

                    $lowConfidence++;
                    continue;
                }

                $oldPricing = $tool->pricing;
                $tool->update([
                    'pricing' => $category,
                    'last_enriched_at' => now(),
                ]);

                Log::info("Updated pricing for tool {$tool->id}", [
                    'tool_name' => $toolName,
                    'old' => $oldPricing,
                    'new' => $category,
                    'confidence' => $confidence,
                    'evidence' => $evidence,
                ]);

                $modified++;
            } catch (Throwable $e) {
                Log::warning("Failed to parse pricing for tool {$tool->id}: " . $e->getMessage(), [
                    'tool_name' => $toolName,
                    'response' => substr((string) $response, 0, 500),
                ]);
                $errors++;
            }
        }

        Log::info('Pricing refresh completed', [
            'verified' => $verified,
            'modified' => $modified,
            'errors' => $errors,
            'low_confidence' => $lowConfidence,
        ]);

        $this->info("Verified: {$verified}, Modified: {$modified}, Errors: {$errors}, Low Confidence: {$lowConfidence}");

        return self::SUCCESS;
    }

    private function buildPrompt(string $toolName, string $toolUrl): string
    {
        return <<<PROMPT
Classify the pricing of {$toolName} ({$toolUrl}) accurately. Return ONLY a JSON object matching this schema:
{"category": one of ["free", "freemium", "paid", "free_trial", "open_source", "enterprise"], "confidence": float 0-1, "evidence": "short quote from pricing page"}

DEFINITIONS:
- free: indefinitely free, no payment required ever
- freemium: permanent free tier with limits + paid upgrades
- free_trial: time-limited trial then paid (e.g. '14-day free trial' = free_trial NOT free)
- paid: paid only, no free tier or trial
- open_source: source code public (MIT/Apache/GPL/etc.) self-hostable
- enterprise: no public pricing, contact sales

FEW-SHOT EXAMPLES:
- TaskShell '\$4.99/mo with 14-day free trial' -> {"category":"free_trial","confidence":0.95,"evidence":"\$4.99/mo with 14-day trial"}
- ChatGPT 'Free with usage limits, \$20/mo Plus' -> {"category":"freemium","confidence":0.95,"evidence":"free tier + Plus"}
- Stability AI 'open source MIT license self-hostable' -> {"category":"open_source","confidence":0.95,"evidence":"MIT license"}
- Midjourney 'Subscriptions start at \$10/mo, no free tier' -> {"category":"paid","confidence":0.95,"evidence":"\$10/mo, no free tier"}

Return ONLY the JSON, no preamble.
PROMPT;
    }
}
