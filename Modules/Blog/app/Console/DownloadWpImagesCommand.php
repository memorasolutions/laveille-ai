<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Blog\Models\Article;
use Throwable;

class DownloadWpImagesCommand extends Command
{
    protected $signature = 'blog:download-wp-images {--url=https://laveilledestef.com}';

    protected $description = 'Télécharge les images featured des articles WP (thumbnails accessibles).';

    public function handle(): int
    {
        $wpUrl = rtrim((string) $this->option('url'), '/');
        $disk = Storage::disk('public');
        $stats = ['downloaded' => 0, 'skipped' => 0, 'failed' => 0];
        $page = 1;

        $this->info("Téléchargement images depuis : {$wpUrl}");

        do {
            $response = Http::get("{$wpUrl}/wp-json/wp/v2/posts", [
                'per_page' => 20,
                'page' => $page,
                '_fields' => 'id,slug,featured_media',
            ]);

            if ($response->failed()) {
                break;
            }

            $posts = $response->json();
            if (empty($posts)) {
                break;
            }

            foreach ($posts as $post) {
                $slug = $post['slug'];
                $mediaId = $post['featured_media'] ?? 0;

                if ($mediaId <= 0) {
                    continue;
                }

                $article = Article::where('slug->fr_CA', $slug)->first();
                if (! $article) {
                    continue;
                }

                // Skip si image déjà téléchargée
                $existingFiles = glob(storage_path("app/public/blog/{$slug}.*"));
                if (! empty($existingFiles)) {
                    $ext = pathinfo($existingFiles[0], PATHINFO_EXTENSION);
                    $article->featured_image = "storage/blog/{$slug}.{$ext}";
                    $article->save();
                    $stats['skipped']++;

                    continue;
                }

                try {
                    $mediaRes = Http::timeout(10)->get("{$wpUrl}/wp-json/wp/v2/media/{$mediaId}");
                    if ($mediaRes->failed()) {
                        $stats['failed']++;

                        continue;
                    }

                    $sizes = $mediaRes->json()['media_details']['sizes'] ?? [];
                    $validUrl = null;

                    foreach (['large', 'medium_large', 'full', 'medium'] as $sizeKey) {
                        $candidateUrl = $sizes[$sizeKey]['source_url'] ?? null;
                        if (! $candidateUrl) {
                            continue;
                        }

                        if (Http::timeout(5)->head($candidateUrl)->successful()) {
                            $validUrl = $candidateUrl;
                            break;
                        }
                    }

                    if (! $validUrl) {
                        $stats['failed']++;
                        $this->line(" <comment>Aucune image accessible pour : {$slug}</comment>");

                        continue;
                    }

                    $ext = explode('?', pathinfo($validUrl, PATHINFO_EXTENSION) ?: 'jpg')[0];
                    $fileName = "blog/{$slug}.{$ext}";

                    $imageContent = Http::timeout(30)->get($validUrl)->body();
                    $disk->put($fileName, $imageContent);

                    $article->featured_image = "storage/{$fileName}";
                    $article->save();

                    $stats['downloaded']++;
                    $this->line(" <info>✓</info> {$slug}");

                } catch (Throwable) {
                    $stats['failed']++;
                }
            }

            $page++;
        } while (! empty($posts));

        $this->newLine();
        $this->table(['Métrique', 'Nombre'], [
            ['Téléchargés', $stats['downloaded']],
            ['Déjà existants', $stats['skipped']],
            ['Échecs (404)', $stats['failed']],
        ]);

        return self::SUCCESS;
    }
}
