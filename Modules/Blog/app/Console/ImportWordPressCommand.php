<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Blog\Models\Tag;
use Throwable;

class ImportWordPressCommand extends Command
{
    protected $signature = 'blog:import-wordpress
                            {--url=https://laveilledestef.com : URL de base du site WordPress}
                            {--per-page=10 : Nombre d\'articles par page API}';

    protected $description = 'Importe les articles, catégories et tags depuis WordPress via REST API (idempotent).';

    protected array $categoryMapping = [];

    protected array $tagMapping = [];

    public function handle(): int
    {
        $baseUrl = rtrim($this->option('url'), '/');
        $perPage = (int) $this->option('per-page');

        $this->info("Import WordPress depuis : {$baseUrl}");

        $this->importCategories($baseUrl);
        $this->importTags($baseUrl);
        $this->importPosts($baseUrl, $perPage);

        $this->newLine();
        $this->info('Import terminé.');

        return self::SUCCESS;
    }

    protected function importCategories(string $baseUrl): void
    {
        $this->info('Import des catégories...');

        $response = Http::get("{$baseUrl}/wp-json/wp/v2/categories", ['per_page' => 100]);

        if ($response->failed()) {
            $this->error('Erreur récupération catégories.');

            return;
        }

        foreach ($response->json() as $wpCat) {
            $name = html_entity_decode($wpCat['name'], ENT_QUOTES, 'UTF-8');
            $slug = $wpCat['slug'];

            $category = Category::where('slug->fr_CA', $slug)
                ->orWhere('slug', $slug)
                ->first();

            if (! $category) {
                $category = new Category;
            }

            $category->setTranslation('name', 'fr_CA', $name);
            $category->setTranslation('slug', 'fr_CA', $slug);
            $category->save();

            $this->categoryMapping[$wpCat['id']] = $category->id;
        }

        $this->info(count($this->categoryMapping).' catégories traitées.');
    }

    protected function importTags(string $baseUrl): void
    {
        $this->info('Import des tags...');

        $response = Http::get("{$baseUrl}/wp-json/wp/v2/tags", ['per_page' => 100]);

        if ($response->failed()) {
            $this->error('Erreur récupération tags.');

            return;
        }

        foreach ($response->json() as $wpTag) {
            $name = html_entity_decode($wpTag['name'], ENT_QUOTES, 'UTF-8');
            $slug = $wpTag['slug'];

            $tag = Tag::where('slug', $slug)->first();

            if (! $tag) {
                $tag = new Tag;
                $tag->slug = $slug;
            }

            $tag->name = $name;
            $tag->save();

            $this->tagMapping[$wpTag['id']] = $tag->id;
        }

        $this->info(count($this->tagMapping).' tags traités.');
    }

    protected function importPosts(string $baseUrl, int $perPage): void
    {
        $headResponse = Http::head("{$baseUrl}/wp-json/wp/v2/posts", ['per_page' => $perPage]);
        $totalPages = (int) $headResponse->header('X-WP-TotalPages', '1');
        $totalPosts = (int) $headResponse->header('X-WP-Total', '0');

        $this->info("Import de {$totalPosts} articles ({$totalPages} pages)...");

        $bar = $this->output->createProgressBar($totalPosts);
        $bar->start();

        $imported = 0;
        $updated = 0;

        for ($page = 1; $page <= $totalPages; $page++) {
            $response = Http::get("{$baseUrl}/wp-json/wp/v2/posts", [
                'per_page' => $perPage,
                'page' => $page,
                '_embed' => true,
            ]);

            if ($response->failed()) {
                $this->error(" Erreur page {$page}");

                continue;
            }

            foreach ($response->json() as $post) {
                $isNew = $this->processPost($post);
                $isNew ? $imported++ : $updated++;
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("Articles : {$imported} importés, {$updated} mis à jour.");
    }

    protected function processPost(array $post): bool
    {
        $slug = $post['slug'];
        $title = html_entity_decode($post['title']['rendered'], ENT_QUOTES, 'UTF-8');
        $content = $post['content']['rendered'];
        $excerpt = trim(strip_tags(html_entity_decode($post['excerpt']['rendered'], ENT_QUOTES, 'UTF-8')));

        // Lookup idempotent sur slug translatable
        $article = Article::where('slug->fr_CA', $slug)
            ->orWhere('slug', $slug)
            ->first();

        $isNew = ! $article;

        if (! $article) {
            $article = new Article;
        }

        // Champs traduits
        $article->setTranslation('title', 'fr_CA', $title);
        $article->setTranslation('slug', 'fr_CA', $slug);
        $article->setTranslation('content', 'fr_CA', $content);
        $article->setTranslation('excerpt', 'fr_CA', $excerpt);

        // Champs non traduits
        $article->status = 'published';
        $article->published_at = Carbon::parse($post['date']);
        $article->user_id = 1;
        $article->format = 'standard';
        $article->is_featured = false;

        // Catégorie (première mappée)
        if (! empty($post['categories'])) {
            foreach ($post['categories'] as $wpCatId) {
                if (isset($this->categoryMapping[$wpCatId])) {
                    $article->category_id = $this->categoryMapping[$wpCatId];
                    break;
                }
            }
        }

        // Image featured
        if (isset($post['_embedded']['wp:featuredmedia'][0]['source_url'])) {
            $imageUrl = $post['_embedded']['wp:featuredmedia'][0]['source_url'];
            $imagePath = $this->downloadImage($imageUrl, $slug);

            if ($imagePath) {
                $article->featured_image = $imagePath;
            }
        }

        $article->save();

        // Tags
        $tagIds = [];
        foreach ($post['tags'] ?? [] as $wpTagId) {
            if (isset($this->tagMapping[$wpTagId])) {
                $tagIds[] = $this->tagMapping[$wpTagId];
            }
        }

        if (! empty($tagIds)) {
            $article->tagsRelation()->sync($tagIds);
        }

        return $isNew;
    }

    protected function downloadImage(string $url, string $slug): ?string
    {
        // Skip si l'image existe déjà
        $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'jpg';
        $filename = "{$slug}.{$extension}";
        $path = "blog/{$filename}";

        if (Storage::disk('public')->exists($path)) {
            return "storage/{$path}";
        }

        try {
            $response = Http::timeout(30)->get($url);

            if ($response->failed()) {
                return null;
            }

            Storage::disk('public')->put($path, $response->body());

            return "storage/{$path}";
        } catch (Throwable) {
            return null;
        }
    }
}
