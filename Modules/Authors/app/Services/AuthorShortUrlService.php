<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

final class AuthorShortUrlService
{
    public function createForArticle(string $authorSlug, string $articleSlug, string $destinationUrl): string
    {
        $slug = '@'.$authorSlug.'/'.$articleSlug;
        $this->upsertSlug($slug, $destinationUrl);

        return Config::get('app.short_url_domain', 'https://veille.la').'/'.$slug;
    }

    public function createForProfile(string $authorSlug, string $profileUrl): string
    {
        $slug = '@'.$authorSlug;
        $this->upsertSlug($slug, $profileUrl);

        return Config::get('app.short_url_domain', 'https://veille.la').'/'.$slug;
    }

    public function delete(string $slug): bool
    {
        return DB::table('short_urls')
            ->where('slug', $slug)
            ->update(['deleted_at' => now()]) > 0;
    }

    public function isReservedSlug(string $slug): bool
    {
        return str_starts_with($slug, '@');
    }

    public static function bootValidation(): void
    {
        // Hook à appeler depuis Modules\ShortUrl\ServiceProvider::boot()
        // pour empêcher tout user non-auteur de créer slug commençant par @
    }

    private function upsertSlug(string $slug, string $destinationUrl): void
    {
        $exists = DB::table('short_urls')
            ->where('slug', $slug)
            ->exists();

        if ($exists) {
            DB::table('short_urls')
                ->where('slug', $slug)
                ->update([
                    'destination_url' => $destinationUrl,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('short_urls')->insert([
                'slug' => $slug,
                'destination_url' => $destinationUrl,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
