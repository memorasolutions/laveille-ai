<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleXMLElement;

class ImportWordPress extends Command
{
    protected $signature = 'wp:import
        {--file= : Path to the WXR XML file}
        {--types=all : Comma-separated types: posts,pages,categories,tags,comments (default: all)}
        {--dry-run : Simulate import without writing to DB}';

    protected $description = 'Import WordPress WXR/XML export into the application';

    private array $stats = [
        'posts' => ['imported' => 0, 'skipped' => 0],
        'pages' => ['imported' => 0, 'skipped' => 0],
        'categories' => ['imported' => 0, 'skipped' => 0],
        'tags' => ['imported' => 0, 'skipped' => 0],
        'comments' => ['imported' => 0, 'skipped' => 0],
    ];

    private array $statusMap = [
        'publish' => 'published',
        'draft' => 'draft',
        'pending' => 'pending_review',
        'private' => 'archived',
        'trash' => 'archived',
    ];

    private array $userMap = [];

    private array $allItems = [];

    public function handle(): int
    {
        $file = $this->option('file');

        if (! $file) {
            $this->error('The --file option is required.');

            return self::FAILURE;
        }

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $isDryRun = (bool) $this->option('dry-run');
        $typesOption = $this->option('types');
        $types = $typesOption === 'all'
            ? ['categories', 'tags', 'posts', 'pages', 'comments']
            : array_map('trim', explode(',', $typesOption));

        if ($isDryRun) {
            $this->warn('Dry run mode — no data will be written.');
        }

        $this->info("Parsing {$file}...");

        try {
            $xml = new SimpleXMLElement(file_get_contents($file));
        } catch (\Exception $e) {
            $this->error("Invalid XML: {$e->getMessage()}");

            return self::FAILURE;
        }

        $ns = [
            'wp' => 'http://wordpress.org/export/1.2/',
            'content' => 'http://purl.org/rss/1.0/modules/content/',
            'dc' => 'http://purl.org/dc/elements/1.1/',
            'excerpt' => 'http://wordpress.org/export/1.2/excerpt/',
        ];

        // Build user map from wp:author elements
        foreach ($xml->channel->children($ns['wp'])->author as $author) {
            $login = (string) $author->children($ns['wp'])->author_login;
            $email = (string) $author->children($ns['wp'])->author_email;
            $display = (string) $author->children($ns['wp'])->author_display_name;
            $this->userMap[$login] = ['email' => $email, 'name' => $display];
        }

        // Import categories first (from channel-level wp:category elements)
        if (in_array('categories', $types) && class_exists(\Modules\Blog\Models\Category::class)) {
            $this->importCategories($xml, $ns, $isDryRun);
        }

        // Import tags (from channel-level wp:tag elements)
        if (in_array('tags', $types) && class_exists(\Modules\Blog\Models\Tag::class)) {
            $this->importTags($xml, $ns, $isDryRun);
        }

        // Process items (posts, pages, comments)
        $items = $xml->channel->item ?? [];
        $itemsArray = [];
        foreach ($items as $item) {
            $itemsArray[] = $item;
        }
        $this->allItems = $itemsArray;

        if (in_array('posts', $types) && class_exists(\Modules\Blog\Models\Article::class)) {
            $this->importPosts($itemsArray, $ns, $isDryRun);
        }

        if (in_array('pages', $types) && class_exists(\Modules\Pages\Models\StaticPage::class)) {
            $this->importPages($itemsArray, $ns, $isDryRun);
        }

        if (in_array('comments', $types) && class_exists(\Modules\Blog\Models\Comment::class)) {
            $this->importComments($itemsArray, $ns, $isDryRun);
        }

        $this->newLine();
        $this->table(
            ['Type', 'Imported', 'Skipped'],
            collect($this->stats)
                ->filter(fn ($s, $type) => in_array($type, $types))
                ->map(fn ($s, $type) => [$type, $s['imported'], $s['skipped']])
                ->values()
                ->toArray()
        );

        $total = collect($this->stats)->sum('imported');
        $this->info("Done. {$total} items imported.");

        return self::SUCCESS;
    }

    private function importCategories(SimpleXMLElement $xml, array $ns, bool $dryRun): void
    {
        $xml->registerXPathNamespace('wp', $ns['wp']);
        $wpCategories = $xml->xpath('/rss/channel/wp:category') ?: [];
        $count = count($wpCategories);

        if ($count === 0) {
            return;
        }

        $this->info("Importing categories ({$count})...");
        $bar = $this->output->createProgressBar($count);

        $callback = function () use ($wpCategories, $ns, $dryRun, $bar) {
            foreach ($wpCategories as $wpCat) {
                $slug = (string) $wpCat->children($ns['wp'])->category_nicename;
                $name = (string) $wpCat->children($ns['wp'])->cat_name;

                if (! $slug || ! $name) {
                    $bar->advance();

                    continue;
                }

                $locale = app()->getLocale();
                $existing = \Modules\Blog\Models\Category::where("slug->{$locale}", $slug)->first();

                if ($existing) {
                    $this->stats['categories']['skipped']++;
                } elseif (! $dryRun) {
                    \Modules\Blog\Models\Category::create([
                        'name' => $name,
                        'slug' => $slug,
                        'is_active' => true,
                    ]);
                    $this->stats['categories']['imported']++;
                } else {
                    $this->stats['categories']['imported']++;
                }

                $bar->advance();
            }
        };

        $dryRun ? $callback() : DB::transaction($callback);
        $bar->finish();
        $this->newLine();
    }

    private function importTags(SimpleXMLElement $xml, array $ns, bool $dryRun): void
    {
        $xml->registerXPathNamespace('wp', $ns['wp']);
        $wpTags = $xml->xpath('//wp:tag') ?: [];
        $count = count($wpTags);

        if ($count === 0) {
            return;
        }

        $this->info("Importing tags ({$count})...");
        $bar = $this->output->createProgressBar($count);

        $callback = function () use ($wpTags, $ns, $dryRun, $bar) {
            foreach ($wpTags as $wpTag) {
                $slug = (string) $wpTag->children($ns['wp'])->tag_slug;
                $name = (string) $wpTag->children($ns['wp'])->tag_name;

                if (! $slug || ! $name) {
                    $bar->advance();

                    continue;
                }

                $existing = \Modules\Blog\Models\Tag::where('slug', $slug)->first();

                if ($existing) {
                    $this->stats['tags']['skipped']++;
                } elseif (! $dryRun) {
                    \Modules\Blog\Models\Tag::create([
                        'name' => $name,
                        'slug' => $slug,
                    ]);
                    $this->stats['tags']['imported']++;
                } else {
                    $this->stats['tags']['imported']++;
                }

                $bar->advance();
            }
        };

        $dryRun ? $callback() : DB::transaction($callback);
        $bar->finish();
        $this->newLine();
    }

    private function importPosts(array $items, array $ns, bool $dryRun): void
    {
        $posts = array_filter($items, fn ($item) => (string) $item->children($ns['wp'])->post_type === 'post');

        if (empty($posts)) {
            return;
        }

        $this->info('Importing posts ('.count($posts).')...');
        $bar = $this->output->createProgressBar(count($posts));

        $callback = function () use ($posts, $ns, $dryRun, $bar) {
            foreach ($posts as $item) {
                $wpId = (string) $item->children($ns['wp'])->post_id;
                $title = (string) $item->title;
                $slug = Str::slug($title) ?: 'post-'.$wpId;

                // Idempotency via meta->wp_id
                $existing = \Modules\Blog\Models\Article::where('meta->wp_id', $wpId)->first();

                if ($existing) {
                    $this->stats['posts']['skipped']++;
                    $bar->advance();

                    continue;
                }

                if (! $dryRun) {
                    $wpStatus = (string) $item->children($ns['wp'])->status;
                    $content = $this->stripShortcodes(
                        (string) $item->children($ns['content'])->encoded
                    );
                    $excerpt = (string) $item->children($ns['excerpt'])->encoded;
                    $creator = (string) $item->children($ns['dc'])->creator;

                    $userId = $this->resolveUserId($creator);

                    // Collect WP post_meta
                    $postMeta = $this->extractPostMeta($item, $ns);
                    $featuredImageUrl = $this->findFeaturedImage($item, $ns, $this->allItems);

                    $fallbackUserId = $userId ?? User::first()?->id ?? 1;

                    $article = \Modules\Blog\Models\Article::create([
                        'title' => $title,
                        'slug' => $slug,
                        'content' => $content,
                        'excerpt' => $excerpt ?: null,
                        'status' => $this->statusMap[$wpStatus] ?? 'draft',
                        'user_id' => $fallbackUserId,
                        'meta' => array_merge($postMeta, ['wp_id' => $wpId]),
                    ]);

                    if ($featuredImageUrl && method_exists($article, 'addMediaFromUrl')) {
                        try {
                            $article->addMediaFromUrl($featuredImageUrl)->toMediaCollection('featured');
                        } catch (\Exception $e) {
                            $this->warn(" [image failed: {$e->getMessage()}]");
                        }
                    }
                }

                $this->stats['posts']['imported']++;
                $bar->advance();
            }
        };

        $dryRun ? $callback() : DB::transaction($callback);
        $bar->finish();
        $this->newLine();
    }

    private function importPages(array $items, array $ns, bool $dryRun): void
    {
        $pages = array_filter($items, fn ($item) => (string) $item->children($ns['wp'])->post_type === 'page');

        if (empty($pages)) {
            return;
        }

        $this->info('Importing pages ('.count($pages).')...');
        $bar = $this->output->createProgressBar(count($pages));

        $callback = function () use ($pages, $ns, $dryRun, $bar) {
            foreach ($pages as $item) {
                $wpId = (string) $item->children($ns['wp'])->post_id;
                $title = (string) $item->title;
                $slug = Str::slug($title) ?: 'page-'.$wpId;

                // Idempotency via slug (translatable JSON column)
                $locale = app()->getLocale();
                $existing = \Modules\Pages\Models\StaticPage::where("slug->{$locale}", $slug)->first();

                if ($existing) {
                    $this->stats['pages']['skipped']++;
                    $bar->advance();

                    continue;
                }

                if (! $dryRun) {
                    $wpStatus = (string) $item->children($ns['wp'])->status;
                    $content = $this->stripShortcodes(
                        (string) $item->children($ns['content'])->encoded
                    );

                    \Modules\Pages\Models\StaticPage::create([
                        'title' => $title,
                        'slug' => $slug,
                        'content' => $content,
                        'status' => $this->statusMap[$wpStatus] ?? 'draft',
                    ]);
                }

                $this->stats['pages']['imported']++;
                $bar->advance();
            }
        };

        $dryRun ? $callback() : DB::transaction($callback);
        $bar->finish();
        $this->newLine();
    }

    private function importComments(array $items, array $ns, bool $dryRun): void
    {
        $postsWithComments = array_filter($items, function ($item) use ($ns) {
            $type = (string) $item->children($ns['wp'])->post_type;

            return $type === 'post' && $item->children($ns['wp'])->comment->count() > 0;
        });

        if (empty($postsWithComments)) {
            return;
        }

        $totalComments = 0;
        foreach ($postsWithComments as $item) {
            foreach ($item->children($ns['wp'])->comment as $_) {
                $totalComments++;
            }
        }

        $this->info("Importing comments ({$totalComments})...");
        $bar = $this->output->createProgressBar($totalComments);

        $callback = function () use ($postsWithComments, $ns, $dryRun, $bar) {
            foreach ($postsWithComments as $item) {
                $wpPostId = (string) $item->children($ns['wp'])->post_id;
                $article = \Modules\Blog\Models\Article::where('meta->wp_id', $wpPostId)->first();

                if (! $article) {
                    foreach ($item->children($ns['wp'])->comment as $_) {
                        $this->stats['comments']['skipped']++;
                        $bar->advance();
                    }

                    continue;
                }

                foreach ($item->children($ns['wp'])->comment as $wpComment) {
                    $wpCommentId = (string) $wpComment->children($ns['wp'])->comment_id;
                    $authorName = (string) $wpComment->children($ns['wp'])->comment_author;
                    $authorEmail = (string) $wpComment->children($ns['wp'])->comment_author_email;
                    $content = $this->stripShortcodes(
                        (string) $wpComment->children($ns['wp'])->comment_content
                    );
                    $approved = (string) $wpComment->children($ns['wp'])->comment_approved;

                    // Idempotency: check guest_name + article_id + content hash
                    $existing = \Modules\Blog\Models\Comment::where('article_id', $article->id)
                        ->where('guest_name', $authorName)
                        ->where('content', $content)
                        ->first();

                    if ($existing) {
                        $this->stats['comments']['skipped']++;
                        $bar->advance();

                        continue;
                    }

                    if (! $dryRun) {
                        \Modules\Blog\Models\Comment::create([
                            'article_id' => $article->id,
                            'guest_name' => $authorName ?: 'Anonymous',
                            'guest_email' => $authorEmail ?: null,
                            'content' => $content,
                            'status' => $approved === '1' ? 'approved' : 'pending',
                        ]);
                    }

                    $this->stats['comments']['imported']++;
                    $bar->advance();
                }
            }
        };

        $dryRun ? $callback() : DB::transaction($callback);
        $bar->finish();
        $this->newLine();
    }

    private function stripShortcodes(string $text): string
    {
        // Remove self-closing shortcodes: [shortcode attr="val" /]
        $text = (string) preg_replace('/\[\w+[^\]]*\/\]/', '', $text);

        // Remove wrapping shortcodes but keep inner content: [shortcode]content[/shortcode]
        $text = (string) preg_replace('/\[\/?[^\]]+\]/', '', $text);

        return trim($text);
    }

    private function resolveUserId(string $login): ?int
    {
        if (! $login || ! isset($this->userMap[$login])) {
            return null;
        }

        $info = $this->userMap[$login];
        $user = User::where('email', $info['email'])->first();

        return $user?->id;
    }

    private function extractPostMeta(SimpleXMLElement $item, array $ns): array
    {
        $meta = [];

        foreach ($item->children($ns['wp'])->postmeta as $pm) {
            $key = (string) $pm->children($ns['wp'])->meta_key;
            $value = (string) $pm->children($ns['wp'])->meta_value;

            // Skip internal WP keys
            if (str_starts_with($key, '_')) {
                continue;
            }

            $meta[$key] = $value;
        }

        return $meta;
    }

    private function findFeaturedImage(SimpleXMLElement $item, array $ns, array $allItems): ?string
    {
        foreach ($item->children($ns['wp'])->postmeta as $pm) {
            $key = (string) $pm->children($ns['wp'])->meta_key;

            if ($key !== '_thumbnail_id') {
                continue;
            }

            $thumbnailId = (string) $pm->children($ns['wp'])->meta_value;

            foreach ($allItems as $attachment) {
                if ((string) $attachment->children($ns['wp'])->post_type !== 'attachment') {
                    continue;
                }

                if ((string) $attachment->children($ns['wp'])->post_id === $thumbnailId) {
                    return (string) $attachment->children($ns['wp'])->attachment_url;
                }
            }
        }

        return null;
    }
}
