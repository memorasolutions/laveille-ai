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

class MigrateContentImagesCommand extends Command
{
    protected $signature = 'blog:migrate-content-images';

    protected $description = 'Télécharge les images WP du contenu des articles et remappe les URLs.';

    private const EXPORT_TOKEN = 'xK9mP2vL7nQ4';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $disk->makeDirectory('blog/content');

        $articles = Article::all();
        $bar = $this->output->createProgressBar($articles->count());
        $stats = ['images' => 0, 'skipped' => 0, 'links' => 0, 'articles' => 0, 'failed' => 0];

        foreach ($articles as $article) {
            $content = $article->getTranslation('content', 'fr_CA');
            $original = $content;

            // 1. Remplacer les liens internes WP → /blog/slug
            $content = preg_replace_callback(
                '#href="https://laveilledestef\.com/\d{4}/\d{2}/\d{2}/([^"/]+)/?"#',
                function ($m) use (&$stats) {
                    $stats['links']++;

                    return 'href="/blog/'.$m[1].'"';
                },
                $content
            );

            // 2. Télécharger et remapper les images
            $content = preg_replace_callback(
                '#(src|srcset)="(https://laveilledestef\.com/wp-content/uploads/([^"]+))"#',
                function ($m) use ($disk, &$stats) {
                    $attr = $m[1];
                    $wpUrl = $m[2];
                    $relativePath = $m[3];

                    $hash = md5($relativePath);
                    $ext = pathinfo($relativePath, PATHINFO_EXTENSION) ?: 'jpg';
                    $localFile = "blog/content/{$hash}.{$ext}";

                    if ($disk->exists($localFile)) {
                        $stats['skipped']++;

                        return $attr.'="'.asset("storage/{$localFile}").'"';
                    }

                    try {
                        $exportUrl = 'https://laveilledestef.com/_export_images.php?token='.self::EXPORT_TOKEN.'&path='.urlencode($relativePath);
                        $response = Http::timeout(15)->get($exportUrl);

                        if ($response->successful() && strlen($response->body()) > 100) {
                            $disk->put($localFile, $response->body());
                            $stats['images']++;

                            return $attr.'="'.asset("storage/{$localFile}").'"';
                        }
                    } catch (Throwable) {
                        // keep original URL
                    }

                    $stats['failed']++;

                    return $m[0]; // garder l'original
                },
                $content
            );

            if ($content !== $original) {
                $article->setTranslation('content', 'fr_CA', $content);
                $article->save();
                $stats['articles']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Métrique', 'Nombre'], [
            ['Articles modifiés', $stats['articles']],
            ['Images téléchargées', $stats['images']],
            ['Images déjà présentes', $stats['skipped']],
            ['Images échouées', $stats['failed']],
            ['Liens internes remappés', $stats['links']],
        ]);

        return self::SUCCESS;
    }
}
