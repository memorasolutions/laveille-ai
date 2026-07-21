<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AI\Exceptions\AiBudgetExceededException;
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
            Setting::get('ai.default_model', 'openrouter/free'),
            Setting::get('ai.chatbot_model', 'openrouter/free'),
            Setting::get('ai.content_model', 'openrouter/free'),
            Setting::get('ai.moderation_model', 'openrouter/free'),
            Setting::get('ai.seo_model', 'openrouter/free'),
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

        // openrouter/free : routeur OpenRouter qui pointe automatiquement vers un modèle
        // gratuit disponible - plus robuste qu'un slug figé (le catalogue gratuit OpenRouter
        // tourne régulièrement ; un ancien modèle codé en dur ici finit par répondre 404).
        return Setting::get($settingKey, Setting::get('ai.default_model', 'openrouter/free'));
    }

    /**
     * Mots-clés FR-QC signalant une tâche complexe (analyse/synthèse/planification longue) :
     * déclenchent une escalade DIRECTE vers le modèle puissant, sans passer par le modèle
     * primaire (évite un aller-retour inutile). Liste éditable via le réglage
     * `ai.escalation_keywords` (CSV) sans toucher au code — voir panneau Réglages > IA.
     */
    private const DEFAULT_ESCALATION_KEYWORDS = 'analyse complète,compare en détail,rédige un plan,explique en profondeur,audit complet,stratégie détaillée,démontre étape par étape,rédige un rapport détaillé';

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     *
     * @throws AiBudgetExceededException  Budget mensuel IA dépassé (`ai.monthly_budget`).
     *                                     Point d'entrée UNIQUE de tous les appelants
     *                                     (Tuteur/Feedback/Authoring/Traduction Academy +
     *                                     cascade d'escalade interne) — DRY strict, un seul
     *                                     disjoncteur pour tout le chemin `chatWithHistory()`.
     */
    public function chatWithHistory(array $messages, ?string $model = null): string
    {
        if (! $this->checkBudget()) {
            Log::warning('AI Service: Monthly budget exceeded');

            throw new AiBudgetExceededException('Budget IA mensuel dépassé.');
        }

        $apiKey = Setting::get('ai.openrouter_api_key');

        if (! $apiKey) {
            Log::error('AI Service: OpenRouter API key not configured');

            return '';
        }

        if (! $this->isEscalationEnabled()) {
            $model ??= $this->getModelForTask('chatbot');

            return $this->performChatRequest($messages, $model, $apiKey);
        }

        $lastUserMessage = $this->extractLastUserMessage($messages);
        $heuristicReason = $this->detectHeuristicEscalation($lastUserMessage);

        if ($heuristicReason !== null) {
            $escalationModel = trim((string) Setting::get('ai.model_escalation', '')) ?: 'deepseek/deepseek-v3.2-20251201';
            Log::info('AI Service: escalade heuristique déclenchée', ['reason' => $heuristicReason]);

            return $this->performChatRequest($messages, $escalationModel, $apiKey);
        }

        if ($model !== null) {
            $primaryModel = $model;
        } else {
            $configuredPrimary = trim((string) Setting::get('ai.model_primary', ''));
            $primaryModel = $configuredPrimary !== '' ? $configuredPrimary : $this->getModelForTask('chatbot');
        }

        $escalationModel = trim((string) Setting::get('ai.model_escalation', '')) ?: 'deepseek/deepseek-v3.2-20251201';

        $messagesWithInstruction = $this->appendEscalationInstruction($messages);
        $primaryResponse = $this->performChatRequest($messagesWithInstruction, $primaryModel, $apiKey);

        if ($primaryResponse === '') {
            Log::info('AI Service: escalade suite à l’échec du modèle primaire');

            return $this->performChatRequest($messages, $escalationModel, $apiKey);
        }

        $markerReason = $this->detectEscalationMarker($primaryResponse);

        if ($markerReason !== null) {
            Log::info('AI Service: escalade signalée par le modèle', ['reason' => $markerReason]);

            return $this->performChatRequest($messages, $escalationModel, $apiKey);
        }

        return $primaryResponse;
    }

    /**
     * Effectue l'appel HTTP OpenRouter et retourne le contenu de la réponse (ou '' en cas
     * d'échec). Extrait du corps historique de chatWithHistory() pour être réutilisé par le
     * chemin standard ET par la cascade d'escalade (DRY strict — un seul point d'appel HTTP).
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    private function performChatRequest(array $messages, string $model, string $apiKey): string
    {
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
     * Cascade activable via le réglage `ai.escalation_enabled` (défaut false = comportement
     * inchangé). Tant que désactivé, chatWithHistory() se comporte EXACTEMENT comme avant.
     */
    private function isEscalationEnabled(): bool
    {
        return filter_var(Setting::get('ai.escalation_enabled', 'false'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    private function extractLastUserMessage(array $messages): string
    {
        foreach (array_reverse($messages) as $message) {
            if (($message['role'] ?? '') === 'user') {
                return (string) ($message['content'] ?? '');
            }
        }

        return '';
    }

    /**
     * Filet de sécurité heuristique : déclenche l'escalade SANS attendre la réponse du modèle
     * primaire, sur (1) une question anormalement longue ou (2) un mot-clé de tâche complexe.
     * Seuils/mots-clés éditables via `ai.escalation_length_threshold` / `ai.escalation_keywords`.
     */
    private function detectHeuristicEscalation(string $lastUserMessage): ?string
    {
        $lengthThreshold = (int) Setting::get('ai.escalation_length_threshold', '2000');

        if ($lengthThreshold > 0 && mb_strlen($lastUserMessage) > $lengthThreshold) {
            return 'message très long ('.mb_strlen($lastUserMessage).' caractères)';
        }

        $keywordsCsv = (string) Setting::get('ai.escalation_keywords', self::DEFAULT_ESCALATION_KEYWORDS);
        $keywords = array_map('trim', explode(',', $keywordsCsv));
        $lowerMessage = mb_strtolower($lastUserMessage);

        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($lowerMessage, mb_strtolower($keyword))) {
                return "mot-clé détecté : « {$keyword} »";
            }
        }

        return null;
    }

    /**
     * Ajoute l'invite d'auto-escalade au message système existant (ou en crée un) sans
     * dupliquer le reste de l'historique. N'est utilisée QUE pour l'appel au modèle primaire ;
     * les appels au modèle d'escalade repartent toujours des messages ORIGINAUX.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array<int, array{role: string, content: string}>
     */
    private function appendEscalationInstruction(array $messages): array
    {
        $instruction = "\n\nSi tu juges cette question trop complexe, ambiguë, ou hors de ta capacité à bien répondre, réponds UNIQUEMENT par : ESCALATE: <raison courte> (rien d'autre, aucun autre texte, aucune explication).";

        foreach ($messages as $index => $message) {
            if (($message['role'] ?? '') === 'system') {
                $messages[$index]['content'] = ($message['content'] ?? '').$instruction;

                return $messages;
            }
        }

        array_unshift($messages, ['role' => 'system', 'content' => trim($instruction)]);

        return $messages;
    }

    /**
     * Détecte le marqueur `ESCALATE: <raison>` renvoyé par le modèle primaire. Le marqueur
     * n'est JAMAIS exposé à l'appelant : dès qu'il est détecté, la réponse est jetée et
     * remplacée par celle du modèle d'escalade.
     */
    private function detectEscalationMarker(string $response): ?string
    {
        $trimmed = trim($response);

        if (preg_match('/^ESCALATE:\s*(.+)$/isu', $trimmed, $matches) === 1) {
            $reason = trim($matches[1]);

            return mb_substr($reason !== '' ? $reason : 'raison non précisée', 0, 200);
        }

        return null;
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
            'openrouter/free' => [0.0, 0.0],
            'meta-llama/llama-3.3-70b-instruct:free' => [0.0, 0.0],
            'qwen/qwen3-coder:free' => [0.0, 0.0],
            'deepseek/deepseek-v3.2-20251201' => [0.00025, 0.00038],
        ];

        [$inputRate, $outputRate] = $rates[$model] ?? [0.001, 0.002];

        return ($inputTokens * $inputRate / 1000) + ($outputTokens * $outputRate / 1000);
    }
}
