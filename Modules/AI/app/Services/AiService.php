<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Settings\Models\Setting;

class AiService
{
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';

    public function checkBudget(): bool
    {
        $budget = (float) Setting::get('ai.monthly_budget', '0');

        if ($budget <= 0) {
            return true;
        }

        $monthStart = now()->startOfMonth();
        $spent = \Modules\AI\Models\AiMessage::where('created_at', '>=', $monthStart)
            ->whereNotNull('tokens')
            ->sum('tokens');

        $estimatedCost = $spent * 0.000002;

        return $estimatedCost < $budget;
    }

    public function chat(string $prompt, ?string $systemPrompt = null, ?string $model = null): string
    {
        if (! $this->checkBudget()) {
            Log::warning('AI Service: Monthly budget exceeded');

            return '';
        }

        $apiKey = Setting::get('ai.openrouter_api_key');

        if (! $apiKey) {
            Log::error('AI Service: OpenRouter API key not configured');

            return '';
        }

        $model ??= $this->getModelForTask('chatbot');
        $messages = [];

        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        $messages[] = ['role' => 'user', 'content' => $prompt];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'HTTP-Referer' => config('app.url'),
            ])
                ->retry(2, 100)
                ->post(self::API_URL, [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => (float) Setting::get('ai.temperature', '0.7'),
                    'max_tokens' => (int) Setting::get('ai.max_tokens', '2048'),
                ]);

            $response->throw();

            return $response->json('choices.0.message.content', '');
        } catch (\Exception $e) {
            Log::error('AI Service error: '.$e->getMessage());

            return '';
        }
    }

    public function getAvailableModels(): array
    {
        return [
            Setting::get('ai.default_model', 'meta-llama/llama-3.3-70b-instruct:free'),
            Setting::get('ai.chatbot_model', 'meta-llama/llama-3.3-70b-instruct:free'),
            Setting::get('ai.content_model', 'qwen/qwen3-coder:free'),
            Setting::get('ai.moderation_model', 'meta-llama/llama-3.3-70b-instruct:free'),
            Setting::get('ai.seo_model', 'meta-llama/llama-3.3-70b-instruct:free'),
        ];
    }

    public function getModelForTask(string $task): string
    {
        $mapping = [
            'chatbot' => 'ai.chatbot_model',
            'content' => 'ai.content_model',
            'moderation' => 'ai.moderation_model',
            'seo' => 'ai.seo_model',
            'translation' => 'ai.translation_model',
            'summary' => 'ai.content_model',
        ];

        $settingKey = $mapping[$task] ?? 'ai.default_model';

        return Setting::get($settingKey, Setting::get('ai.default_model', 'meta-llama/llama-3.3-70b-instruct:free'));
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chatWithHistory(array $messages, ?string $model = null): string
    {
        $apiKey = Setting::get('ai.openrouter_api_key');

        if (! $apiKey) {
            Log::error('AI Service: OpenRouter API key not configured');

            return '';
        }

        $model ??= $this->getModelForTask('chatbot');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'HTTP-Referer' => config('app.url'),
            ])
                ->retry(2, 100)
                ->post(self::API_URL, [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => (float) Setting::get('ai.temperature', '0.7'),
                    'max_tokens' => (int) Setting::get('ai.max_tokens', '2048'),
                ]);

            $response->throw();

            return $response->json('choices.0.message.content', '');
        } catch (\Exception $e) {
            Log::error('AI Service error: '.$e->getMessage());

            return '';
        }
    }

    /**
     * @return array{title: string, content: string, excerpt: string, meta_description: string, tags: array<int, string>}
     */
    public function generateArticle(string $topic, string $tone = 'professional', string $length = 'medium', string $locale = 'fr'): array
    {
        $wordCounts = ['short' => 500, 'medium' => 1000, 'long' => 2000];
        $wordCount = $wordCounts[$length] ?? 1000;

        $systemPrompt = "You are a blog article writer. Respond ONLY with valid JSON, no markdown fences. JSON keys: title (string), content (HTML with h2/h3/p/ul/li tags), excerpt (max 160 chars), meta_description (max 160 chars), tags (array of 3-5 strings). Language: {$locale}. Tone: {$tone}. Length: ~{$wordCount} words.";

        $response = $this->chat("Write a blog article about: {$topic}", $systemPrompt, $this->getModelForTask('content'));

        $default = [
            'title' => $topic,
            'content' => '<p>'.($locale === 'fr' ? 'Le contenu n\'a pas pu être généré.' : 'Content could not be generated.').'</p>',
            'excerpt' => $topic,
            'meta_description' => $topic,
            'tags' => [],
        ];

        if (empty($response)) {
            return $default;
        }

        $clean = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $response);

        /** @var array<string, mixed>|null $data */
        $data = json_decode((string) $clean, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            return $default;
        }

        return array_merge($default, $data);
    }

    /**
     * @return array{verdict: string, confidence: float, reason: string, categories: array<int, string>}
     */
    public function moderateContent(string $content): array
    {
        $systemPrompt = 'You are a content moderation assistant. Analyze the provided content for: spam, toxicity, hate speech, harassment, profanity, self-promotion. Respond ONLY with valid JSON: {"verdict": "approve|flag|spam", "confidence": 0.0-1.0, "reason": "explanation", "categories": ["spam","toxic",...]}. No additional text.';

        $response = $this->chat($content, $systemPrompt, $this->getModelForTask('moderation'));

        $default = [
            'verdict' => 'flag',
            'confidence' => 0.1,
            'reason' => 'Unable to parse moderation response',
            'categories' => [],
        ];

        if (empty($response)) {
            return $default;
        }

        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');

        if ($jsonStart === false || $jsonEnd === false) {
            return $default;
        }

        $jsonStr = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);

        /** @var array<string, mixed>|null $result */
        $result = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE
            || ! isset($result['verdict'], $result['confidence'])
            || ! in_array($result['verdict'], ['approve', 'flag', 'spam'])) {
            return $default;
        }

        return [
            'verdict' => $result['verdict'],
            'confidence' => (float) $result['confidence'],
            'reason' => $result['reason'] ?? '',
            'categories' => $result['categories'] ?? [],
        ];
    }

    /**
     * @return array{title: string, description: string, keywords: string, og_title: string, og_description: string}
     */
    public function generateSeoMeta(string $title, string $content): array
    {
        $systemPrompt = 'You are an SEO expert. Generate optimized meta tags for the given article. Respond ONLY with valid JSON: {"title": "SEO title max 60 chars", "description": "meta description max 160 chars", "keywords": "comma,separated,keywords", "og_title": "Open Graph title max 60 chars", "og_description": "OG description max 160 chars"}. No additional text.';

        $prompt = "Article title: {$title}\n\nArticle content (excerpt): ".mb_substr(strip_tags($content), 0, 1000);

        $response = $this->chat($prompt, $systemPrompt, $this->getModelForTask('seo'));

        $default = [
            'title' => mb_substr($title, 0, 60),
            'description' => mb_substr($title, 0, 160),
            'keywords' => '',
            'og_title' => mb_substr($title, 0, 60),
            'og_description' => mb_substr($title, 0, 160),
        ];

        if (empty($response)) {
            return $default;
        }

        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');

        if ($jsonStart === false || $jsonEnd === false) {
            return $default;
        }

        $jsonStr = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);

        /** @var array<string, mixed>|null $result */
        $result = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! isset($result['title'], $result['description'])) {
            return $default;
        }

        return [
            'title' => mb_substr((string) $result['title'], 0, 60),
            'description' => mb_substr((string) $result['description'], 0, 160),
            'keywords' => (string) ($result['keywords'] ?? ''),
            'og_title' => mb_substr((string) ($result['og_title'] ?? $result['title']), 0, 60),
            'og_description' => mb_substr((string) ($result['og_description'] ?? $result['description']), 0, 160),
        ];
    }

    public function generateSummary(string $content, string $locale = 'fr', int $maxLength = 160): string
    {
        $stripped = strip_tags($content);

        if (mb_strlen($stripped) <= $maxLength) {
            return $stripped;
        }

        $systemPrompt = "You are a content summarizer. Summarize the following text concisely in {$locale}. Return ONLY the summary, nothing else. Maximum {$maxLength} characters.";

        $summary = $this->chat($stripped, $systemPrompt, $this->getModelForTask('content'));

        if (! empty($summary)) {
            return mb_substr(trim($summary), 0, $maxLength);
        }

        return mb_substr($stripped, 0, $maxLength);
    }

    public function translateContent(string $content, string $fromLocale, string $toLocale): string
    {
        if (empty($content)) {
            return '';
        }

        $systemPrompt = "You are a professional translator. Translate the text from {$fromLocale} to {$toLocale}. Preserve all HTML tags exactly as they are. Return ONLY the translated text, nothing else.";

        $response = $this->chat($content, $systemPrompt, $this->getModelForTask('translation'));

        return ! empty($response) ? trim($response) : $content;
    }

    /**
     * @return array{score: int, readability: string, seo_tips: array<int, string>, structure_tips: array<int, string>, improvements: array<int, string>}
     */
    public function analyzeContent(string $title, string $content, string $locale = 'fr'): array
    {
        $cleanContent = strip_tags($content);

        $systemPrompt = "You are a content analysis expert. Analyze the provided article. Respond ONLY with valid JSON: {\"score\": 0-100, \"readability\": \"assessment string\", \"seo_tips\": [\"tip1\"], \"structure_tips\": [\"tip1\"], \"improvements\": [\"tip1\"]}. Language: {$locale}. No additional text.";

        $prompt = "Title: {$title}\n\nContent: ".mb_substr($cleanContent, 0, 2000);

        $response = $this->chat($prompt, $systemPrompt, $this->getModelForTask('content'));

        $default = [
            'score' => 50,
            'readability' => 'Unable to analyze',
            'seo_tips' => [],
            'structure_tips' => [],
            'improvements' => [],
        ];

        if (empty($response)) {
            return $default;
        }

        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');

        if ($jsonStart === false || $jsonEnd === false) {
            return $default;
        }

        $jsonStr = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);

        /** @var array<string, mixed>|null $result */
        $result = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE
            || ! isset($result['score'], $result['readability'], $result['seo_tips'], $result['structure_tips'], $result['improvements'])) {
            return $default;
        }

        return [
            'score' => max(0, min(100, (int) $result['score'])),
            'readability' => (string) $result['readability'],
            'seo_tips' => (array) $result['seo_tips'],
            'structure_tips' => (array) $result['structure_tips'],
            'improvements' => (array) $result['improvements'],
        ];
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function streamChat(array $messages, ?string $model = null): \Generator
    {
        $apiKey = Setting::get('ai.openrouter_api_key');

        if (! $apiKey) {
            yield '';

            return;
        }

        $model ??= $this->getModelForTask('chatbot');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'HTTP-Referer' => config('app.url'),
            ])
                ->withOptions(['stream' => true, 'timeout' => 120])
                ->post(self::API_URL, [
                    'model' => $model,
                    'messages' => $messages,
                    'stream' => true,
                    'temperature' => (float) Setting::get('ai.temperature', '0.7'),
                ]);

            /** @var \Psr\Http\Message\StreamInterface $body */
            $body = $response->toPsrResponse()->getBody();

            while (! $body->eof()) {
                $line = '';
                /** @phpstan-ignore booleanNot.alwaysTrue */
                while (! $body->eof()) {
                    $char = $body->read(1);
                    if ($char === "\n") {
                        break;
                    }
                    $line .= $char;
                }

                $line = trim($line);

                if ($line === '' || ! str_starts_with($line, 'data: ')) {
                    continue;
                }

                $data = substr($line, 6);

                if ($data === '[DONE]') {
                    break;
                }

                $parsed = json_decode($data, true);
                $content = $parsed['choices'][0]['delta']['content'] ?? '';

                if ($content !== '') {
                    yield $content;
                }
            }
        } catch (\Exception $e) {
            Log::error('AI Stream error: '.$e->getMessage());
            yield '';
        }
    }

    public function rewriteContent(string $content, string $style = 'professional', string $locale = 'fr'): string
    {
        if (empty(trim($content))) {
            return $content;
        }

        $systemPrompt = "Rewrite the following content in a {$style} style. The output should be in {$locale} locale. Maintain the original meaning but adapt the tone and phrasing. Return ONLY the rewritten text, nothing else.";

        $result = $this->chat($content, $systemPrompt, $this->getModelForTask('content'));

        return ! empty($result) ? trim($result) : $content;
    }

    public function improveContent(string $content, string $locale = 'fr'): string
    {
        if (empty(trim($content))) {
            return $content;
        }

        $systemPrompt = "Improve the following content for better grammar, clarity, and flow. Fix any grammatical errors, awkward phrasing, or unclear sentences. The output should be in {$locale} locale. Return ONLY the improved text, nothing else.";

        $result = $this->chat($content, $systemPrompt, $this->getModelForTask('content'));

        return ! empty($result) ? trim($result) : $content;
    }

    public function estimateCost(int $inputTokens, int $outputTokens, string $model): float
    {
        $rates = [
            'meta-llama/llama-3.3-70b-instruct:free' => [0.0, 0.0],
            'qwen/qwen3-coder:free' => [0.0, 0.0],
            'deepseek/deepseek-v3.2-20251201' => [0.00025, 0.00038],
        ];

        [$inputRate, $outputRate] = $rates[$model] ?? [0.001, 0.002];

        return ($inputTokens * $inputRate / 1000) + ($outputTokens * $outputRate / 1000);
    }
}
