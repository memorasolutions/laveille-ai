<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use Exception;
use Illuminate\Support\Str;
use Modules\Authors\Models\AuthorProfile;
use ZipArchive;

final class AuthorExportService
{
    private const MAX_EXPORT_SIZE_BYTES = 500 * 1024 * 1024;

    public function exportMarkdown(int $authorProfileId): string
    {
        $author = AuthorProfile::findOrFail($authorProfileId);
        $tempDir = storage_path('app/temp/'.Str::uuid());
        $mdDir = $tempDir.'/markdown';
        if (! is_dir($mdDir)) {
            mkdir($mdDir, 0755, true);
        }

        $this->writeArticlesAsMarkdown($author, $mdDir);

        $zipPath = storage_path('app/exports/authors-md-'.$author->slug.'-'.now()->format('Y-m-d').'.zip');
        $this->ensureExportDir();
        $this->createZip($tempDir, $zipPath);
        $this->rrmdir($tempDir);

        return $zipPath;
    }

    public function exportJsonFeed(int $authorProfileId): string
    {
        $author = AuthorProfile::findOrFail($authorProfileId);
        $items = [];

        if (method_exists($author, 'articles')) {
            foreach ($author->articles()->get() as $article) {
                $items[] = [
                    'id' => (string) $article->id,
                    'url' => url('/blog/'.$article->slug),
                    'title' => $article->title,
                    'content_html' => $article->content,
                    'date_published' => optional($article->published_at)->toIso8601String(),
                ];
            }
        }

        $feed = [
            'version' => 'https://jsonfeed.org/version/1.1',
            'title' => $author->slug.' - Articles',
            'home_page_url' => url('/@'.$author->slug),
            'feed_url' => url('/@'.$author->slug.'/feed.json'),
            'description' => $author->bio ?? '',
            'authors' => [['name' => $author->slug, 'url' => url('/@'.$author->slug)]],
            'items' => $items,
        ];

        $jsonPath = storage_path('app/exports/authors-feed-'.$author->slug.'-'.now()->format('Y-m-d').'.json');
        $this->ensureExportDir();
        file_put_contents($jsonPath, json_encode($feed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $jsonPath;
    }

    public function exportFullBackup(int $authorProfileId): string
    {
        $author = AuthorProfile::findOrFail($authorProfileId);
        $tempDir = storage_path('app/temp/'.Str::uuid());
        $backupDir = $tempDir.'/backup';
        mkdir($backupDir, 0755, true);
        mkdir($backupDir.'/articles', 0755, true);
        mkdir($backupDir.'/media', 0755, true);

        $this->writeArticlesAsMarkdown($author, $backupDir.'/articles');

        if (method_exists($author, 'statuses')) {
            file_put_contents($backupDir.'/statuses.json', json_encode($author->statuses()->get()->toArray(), JSON_PRETTY_PRINT));
        }
        if (method_exists($author, 'curationItems')) {
            file_put_contents($backupDir.'/curation.json', json_encode($author->curationItems()->get()->toArray(), JSON_PRETTY_PRINT));
        }

        file_put_contents($backupDir.'/profile.json', json_encode($author->toArray(), JSON_PRETTY_PRINT));

        $sourceMedia = storage_path('app/public/authors/'.$author->id);
        if (is_dir($sourceMedia)) {
            foreach (glob($sourceMedia.'/*') ?: [] as $file) {
                if (is_file($file)) {
                    copy($file, $backupDir.'/media/'.basename($file));
                }
            }
        }

        $manifest = [
            'export_type' => 'full_backup',
            'author_slug' => $author->slug,
            'exported_at' => now()->toIso8601String(),
            'signal' => 'Tu es libre de partir avec tout ton contenu.',
        ];
        file_put_contents($backupDir.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

        $totalSize = $this->getDirectorySize($backupDir);
        if ($totalSize > self::MAX_EXPORT_SIZE_BYTES) {
            $this->rrmdir($tempDir);
            throw new Exception('Export exceeds 500 MB maximum');
        }

        $zipPath = storage_path('app/exports/authors-backup-'.$author->slug.'-'.now()->format('Y-m-d').'.zip');
        $this->ensureExportDir();
        $this->createZip($backupDir, $zipPath);
        $this->rrmdir($tempDir);

        return $zipPath;
    }

    private function writeArticlesAsMarkdown(AuthorProfile $author, string $dir): void
    {
        if (! method_exists($author, 'articles')) {
            return;
        }

        foreach ($author->articles()->get() as $article) {
            $yaml = "---\n";
            $yaml .= "title: ".$this->escapeYaml($article->title)."\n";
            $yaml .= "slug: ".$article->slug."\n";
            $yaml .= "date_published: ".optional($article->published_at)->toIso8601String()."\n";
            $yaml .= "---\n";

            $content = trim(strip_tags((string) $article->content));
            file_put_contents($dir.'/'.Str::slug($article->title).'.md', $yaml."\n".$content);
        }
    }

    private function escapeYaml(string $str): string
    {
        return preg_match('/[:\[\]{}&*#?|<>=!%@`]/', $str) ? '"'.addcslashes($str, '"\\').'"' : $str;
    }

    private function ensureExportDir(): void
    {
        $dir = storage_path('app/exports');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    private function createZip(string $sourceDir, string $zipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Cannot create zip archive');
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($sourceDir) + 1);
            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
    }

    private function getDirectorySize(string $dir): int
    {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir), ['.', '..']) as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
