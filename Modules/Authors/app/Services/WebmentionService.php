<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorWebmention;

final class WebmentionService
{
    public function receive(string $sourceUrl, string $targetUrl): AuthorWebmention|false
    {
        if (! filter_var($sourceUrl, FILTER_VALIDATE_URL) || ! filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            return false;
        }

        $post = $this->findPostFromTargetUrl($targetUrl);
        if (! $post) {
            return false;
        }

        $existing = AuthorWebmention::where('author_post_id', $post->id)
            ->where('source_url', $sourceUrl)
            ->first();

        if ($existing) {
            $existing->update(['received_at' => now()]);

            return $existing;
        }

        return AuthorWebmention::create([
            'author_post_id' => $post->id,
            'target_url' => $targetUrl,
            'source_url' => $sourceUrl,
            'type' => 'mention',
            'received_at' => now(),
        ]);
    }

    public function verify(AuthorWebmention $webmention): bool
    {
        try {
            $response = Http::timeout(10)->get($webmention->source_url);
            if (! $response->successful()) {
                return false;
            }

            $body = $response->body();
        } catch (\Throwable $e) {
            Log::channel('daily')->warning('webmention.verify.fetch_failed', [
                'url' => $webmention->source_url,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if (! str_contains($body, $webmention->target_url)) {
            return false;
        }

        $authorName = null;
        $authorUrl = null;
        $excerpt = null;

        if (preg_match('/class=["\'][^"\']*\bp-author\b[^"\']*["\'][^>]*>([^<]+)</i', $body, $matches)) {
            $authorName = trim(strip_tags($matches[1]));
        }
        if (preg_match('/<a[^>]*class=["\'][^"\']*\bu-url\b[^"\']*["\'][^>]*href=["\']([^"\']+)["\']/i', $body, $matches)) {
            $authorUrl = $matches[1];
        }
        if (preg_match('/class=["\'][^"\']*\be-content\b[^"\']*["\'][^>]*>(.*?)<\/[^>]+>/is', $body, $matches)) {
            $excerpt = mb_substr(trim(strip_tags($matches[1])), 0, 500);
        }

        $webmention->update([
            'verified_at' => now(),
            'source_author_name' => $authorName,
            'source_author_url' => $authorUrl,
            'source_excerpt' => $excerpt,
        ]);

        return true;
    }

    public function findPostFromTargetUrl(string $targetUrl): ?AuthorPost
    {
        $parsed = parse_url($targetUrl);
        if (! isset($parsed['path'])) {
            return null;
        }

        if (! preg_match('/^\/@([a-z0-9-]+)\/([a-z0-9-]+)$/i', $parsed['path'], $matches)) {
            return null;
        }

        $author = AuthorProfile::where('slug', $matches[1])->first();
        if (! $author) {
            return null;
        }

        return AuthorPost::where('author_profile_id', $author->id)
            ->where('slug', $matches[2])
            ->published()
            ->public()
            ->first();
    }
}
