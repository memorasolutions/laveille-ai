<?php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('routes/console.php contains class_exists wrap for telescope:prune', function () {
    $content = file_get_contents(base_path('routes/console.php'));
    expect($content)->toContain('class_exists(\\Laravel\\Telescope\\Telescope::class)');
    expect($content)->toContain('telescope:prune');
});

it('routes/console.php telescope schedule is conditionally inscribed', function () {
    $content = file_get_contents(base_path('routes/console.php'));
    $pattern = '/if\s*\(\s*class_exists\(\\\\Laravel\\\\Telescope\\\\Telescope::class\)\s*\)\s*\{[^}]*telescope:prune/';
    expect($content)->toMatch($pattern);
});

it('news_articles seo columns are extended to VARCHAR 512 or 2048', function () {
    if (DB::connection()->getDriverName() !== 'mysql') {
        $this->markTestSkipped('Test MySQL only - news_articles VARCHAR sizes (SQLite test env skipped)');
    }
    $columns = DB::select("SHOW COLUMNS FROM news_articles WHERE Field IN ('seo_title','meta_description','image_url')");
    $types = collect($columns)->pluck('Type', 'Field')->map(fn($t) => strtolower($t))->all();
    expect($types['seo_title'])->toContain('varchar(512)');
    expect($types['meta_description'])->toContain('varchar(512)');
    expect($types['image_url'])->toContain('varchar(2048)');
});

it('news_articles seo columns are nullable', function () {
    if (DB::connection()->getDriverName() !== 'mysql') {
        $this->markTestSkipped('Test MySQL only - SHOW COLUMNS nullable check (SQLite test env skipped)');
    }
    $columns = DB::select("SHOW COLUMNS FROM news_articles WHERE Field IN ('seo_title','meta_description','image_url')");
    foreach ($columns as $col) {
        expect($col->Null)->toBe('YES');
    }
});
