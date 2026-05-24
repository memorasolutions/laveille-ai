<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use Illuminate\Support\Facades\Http;

final class SpamDetectionService
{
    public function score(string $body, ?string $email = null, ?string $ip = null, ?string $userAgent = null): int
    {
        $akismetKey = config('services.akismet.key');
        if ($akismetKey !== null && class_exists(Http::class)) {
            $akismetScore = $this->akismetCheck($body, $email, $ip, $userAgent);
            if ($akismetScore !== null) {
                return $akismetScore;
            }
        }

        return $this->computeRegexScore($body, $email, $ip, $userAgent);
    }

    private function computeRegexScore(string $body, ?string $email, ?string $ip, ?string $userAgent): int
    {
        $score = 0;
        $urlCount = preg_match_all('#https?://#i', $body);

        if ($urlCount > 3) {
            $score += 30;
        }
        if ($urlCount > 6) {
            $score += 20;
        }
        if (preg_match('/(.)\\1{4,}/i', $body)) {
            $score += 20;
        }

        $trimmedBody = trim($body);
        $bodyLength = mb_strlen($trimmedBody);
        if ($bodyLength < 5) {
            $score += 15;
        }
        if ($bodyLength < 2) {
            $score += 30;
        }

        $bannedWords = ['viagra', 'cialis', 'casino', 'poker', 'crypto bonus', 'buy now click', 'click here now'];
        foreach ($bannedWords as $word) {
            if (stripos($body, $word) !== false) {
                $score += 25;
            }
        }

        $disposableDomains = ['tempmail', '10minutemail', 'guerrillamail', 'throwawaymail', 'mailinator', 'yopmail', 'sharklasers'];
        if ($email !== null) {
            foreach ($disposableDomains as $domain) {
                if (stripos($email, $domain) !== false) {
                    $score += 25;
                    break;
                }
            }
        }

        if ($userAgent !== null && preg_match('/bot|crawl|spider|curl|wget/i', $userAgent)) {
            $score += 30;
        }

        return min(100, $score);
    }

    private function akismetCheck(string $body, ?string $email, ?string $ip, ?string $userAgent): ?int
    {
        try {
            $key = config('services.akismet.key');
            $blog = config('app.url');
            $response = Http::timeout(3)->asForm()->post(
                "https://{$key}.rest.akismet.com/1.1/comment-check",
                [
                    'blog' => $blog,
                    'comment_content' => $body,
                    'comment_author_email' => $email,
                    'user_ip' => $ip,
                    'user_agent' => $userAgent,
                ]
            );

            if ($response->successful()) {
                return $response->body() === 'true' ? 90 : 10;
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
