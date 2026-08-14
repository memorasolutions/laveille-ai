<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Authors\Mail\ModerationAlertMail;
use Modules\Authors\Models\ModerationLog;
use Modules\Blog\Models\Article;
use Modules\Core\Services\OpenRouterPrivacy;

final class ModerationPipelineService
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) config('services.openrouter.api_key');
    }

    public function scan(Article $article): array
    {
        $scores = [];
        $flags = [];
        $shouldAlert = false;
        $reviewSummary = null;
        $finalStatus = 'approved';

        try {
            $content = (string) ($article->content ?? '');

            $llamaResult = $this->runLlamaGuard($content);
            $scores['llama_guard'] = $llamaResult['severity'] ?? 0;
            if (($llamaResult['severity'] ?? 0) > 5) {
                $flags[] = 'llama_guard_high_severity';
            }

            $gptResult = $this->runGptOss($content);
            $scores['gpt_oss_is_safe'] = $gptResult['is_safe'] ?? true;
            if (! ($gptResult['is_safe'] ?? true)) {
                $flags[] = 'gpt_oss_unsafe';
            }

            $localResult = $this->runLocalRules($article);
            $scores['local_rules'] = $localResult['score'];
            $flags = array_merge($flags, $localResult['flags']);

            $needsEscalation = ($llamaResult['severity'] ?? 0) > 5
                || ! ($gptResult['is_safe'] ?? true)
                || ! empty($localResult['flags']);

            if ($needsEscalation) {
                $claudeResult = $this->runClaudeHaiku($content);
                $scores['claude_haiku_final'] = $claudeResult['final'] ?? 'approve';
                $reviewSummary = $claudeResult['summary'] ?? null;

                if (in_array($claudeResult['final'] ?? 'approve', ['flag', 'reject'], true)) {
                    $finalStatus = $claudeResult['final'] === 'flag' ? 'flagged' : 'rejected';
                    $shouldAlert = true;
                }
            }
        } catch (Exception $e) {
            $finalStatus = 'approved';
            $reviewSummary = 'Erreur pipeline modération, approuvé par défaut.';
            Log::warning('ModerationPipelineService error', ['error' => $e->getMessage(), 'article_id' => $article->id]);
        }

        ModerationLog::create([
            'article_id' => $article->id,
            'llama_guard_score' => $scores['llama_guard'] ?? null,
            'gpt_oss_score' => $scores['gpt_oss_is_safe'] ?? null,
            'local_rules_flags' => $flags,
            'claude_haiku_review' => $reviewSummary,
            'final_status' => $finalStatus,
            'alert_sent_at' => $shouldAlert ? now() : null,
            'alert_recipient' => $shouldAlert ? (string) config('mail.super_admin_email') : null,
        ]);

        if ($shouldAlert) {
            try {
                Mail::to((string) config('mail.super_admin_email'))
                    ->send(new ModerationAlertMail($article, $finalStatus, $reviewSummary));
            } catch (Exception $e) {
                Log::error('Failed to send moderation alert', ['error' => $e->getMessage()]);
            }
        }

        return [
            'final_status' => $finalStatus,
            'scores' => $scores,
            'flags' => $flags,
            'should_alert' => $shouldAlert,
            'review_summary' => $reviewSummary,
        ];
    }

    private function runLlamaGuard(string $content): array
    {
        $response = $this->callOpenRouter('meta-llama/llama-guard-4-12b', "Classify the following content for safety. Return JSON: {categories: [], severity: 0-10}\n\nContent: ".mb_substr($content, 0, 4000));
        return $this->safeJsonDecode($response, ['severity' => 0]);
    }

    private function runGptOss(string $content): array
    {
        $response = $this->callOpenRouter('openai/gpt-oss-120b:free', "Is this content safe? Return JSON: {is_safe: bool, reason: string}\n\nContent: ".mb_substr($content, 0, 4000));
        return $this->safeJsonDecode($response, ['is_safe' => true]);
    }

    private function runClaudeHaiku(string $content): array
    {
        $response = $this->callOpenRouter('anthropic/claude-haiku-4-5', "Review this content flagged by automated systems. Explain in 2 sentences if it's actually problematic. Output: {final: 'approve'|'flag'|'reject', summary: string}\n\nContent: ".mb_substr($content, 0, 4000));
        return $this->safeJsonDecode($response, ['final' => 'approve', 'summary' => '']);
    }

    private function runLocalRules(Article $article): array
    {
        $flags = [];
        $score = 0;
        $content = strtolower((string) $article->content);

        $spamKeywords = ['viagra', 'casino', 'free money', 'click here', 'act now'];
        foreach ($spamKeywords as $kw) {
            if (str_contains($content, $kw)) {
                $flags[] = 'spam_keyword';
                $score += 3;
                break;
            }
        }

        $blacklistDomains = ['spammy-site.com', 'phishy.net'];
        foreach ($blacklistDomains as $d) {
            if (str_contains($content, $d)) {
                $flags[] = 'blacklisted_domain';
                $score += 5;
                break;
            }
        }

        if ($article->user_id) {
            $recentCount = Article::where('user_id', $article->user_id)
                ->where('created_at', '>=', now()->subHour())
                ->count();
            if ($recentCount > 5) {
                $flags[] = 'excessive_posting_rate';
                $score += 6;
            }
        }

        return ['score' => $score, 'flags' => $flags];
    }

    private function callOpenRouter(string $model, string $prompt): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://openrouter.ai/api/v1/chat/completions', OpenRouterPrivacy::applyTo([
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]));

        if (! $response->successful()) {
            throw new Exception("OpenRouter API error ({$model}): ".$response->status());
        }

        return (string) $response->json('choices.0.message.content');
    }

    private function safeJsonDecode(string $content, array $default): array
    {
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $default;
    }
}
