<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;

class ContentQualityService
{
    public function check(string $text, ?int $userId = null): ContentQualityResult
    {
        $reasons = [];
        $score = 100;

        if ($this->isGibberish($text)) {
            $score -= 20;
            $reasons[] = __('Le texte semble illisible ou non pertinent.');
        }

        if ($this->isDuplicate($text, $userId)) {
            $score -= 30;
            $reasons[] = __('Contenu trop similaire à un message récent.');
        }

        if ($this->hasExcessiveUrls($text)) {
            $score -= 15;
            $reasons[] = __('Trop de liens dans le message.');
        }

        if ($this->isSpammy($text)) {
            $score -= 25;
            $reasons[] = __('Contenu suspect de spam.');
        }

        $score = max(0, $score);

        return new ContentQualityResult(
            passed: $score >= 70,
            reasons: $reasons,
            score: $score,
        );
    }

    public function isDuplicate(string $text, ?int $userId = null, string $table = 'directory_discussions', string $column = 'body'): bool
    {
        $normalized = mb_strtolower(trim(strip_tags($text)));
        if ($normalized === '') {
            return false;
        }

        $hash = md5($normalized);

        $query = DB::table($table)
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('id', 'desc')
            ->select($column, 'user_id');

        if ($userId !== null) {
            $query->where('user_id', '!=', $userId);
        }

        foreach ($query->lazy(100) as $row) {
            $existingHash = md5(mb_strtolower(trim(strip_tags($row->{$column} ?? ''))));
            if ($existingHash === $hash) {
                return true;
            }
        }

        return false;
    }

    public function isGibberish(string $text): bool
    {
        $text = trim($text);
        if (mb_strlen($text) < 10) {
            return true;
        }

        $cleanText = preg_replace('/[^a-zàâäéèêëïîôöùûüÿç]/i', '', $text);
        $totalChars = mb_strlen($cleanText);
        if ($totalChars === 0) {
            return true;
        }

        $vowels = preg_match_all('/[aeiouàâäéèêëïîôöùûü]/i', $cleanText);
        if ($vowels / $totalChars <= 0.10) {
            return true;
        }

        $words = preg_split('/\s+/', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) === 0) {
            return true;
        }

        $uniqueWords = array_unique($words);
        if (count($uniqueWords) / count($words) < 0.60) {
            return true;
        }

        if (count($uniqueWords) < 3) {
            return true;
        }

        $chars = mb_str_split(mb_strtolower($text));
        $charCounts = array_count_values($chars);
        arsort($charCounts);
        $maxCharCount = reset($charCounts);
        if ($maxCharCount / count($chars) >= 0.30) {
            return true;
        }

        return false;
    }

    public function hasExcessiveUrls(string $text, int $maxUrls = 2): bool
    {
        preg_match_all('/https?:\/\/[^\s<>"\']+/i', $text, $matches);

        return count($matches[0]) > $maxUrls;
    }

    public function isSpammy(string $text): bool
    {
        $blacklist = [
            'casino', 'viagra', 'crypto giveaway', 'click here', 'buy now',
            'make money fast', 'free gift', 'act now', 'limited offer',
            'win cash', 'no risk', 'guaranteed income', 'get rich',
            'earn from home', 'double your', 'nigerian prince', 'wire transfer',
        ];

        $lowerText = mb_strtolower($text);
        foreach ($blacklist as $pattern) {
            if (str_contains($lowerText, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
