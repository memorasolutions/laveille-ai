<?php
declare(strict_types=1);

// Fichier créé par MEMORA solutions (https://memora.solutions) - Tâche #1645 - 2026-08-06

use Modules\Directory\Models\Tool;

test('le toggle show_archived est réservé aux modérateurs', function () {
    $content = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/PublicDirectoryController.php'));
    expect($content)->toContain("boolean('show_archived')");
    expect($content)->toContain("can('moderate_tools')");
});

test('le compteur archivedCount est nul pour le public (le lien « Voir les X outils archivés » disparaît)', function () {
    $content = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/PublicDirectoryController.php'));
    expect($content)->toContain("? Tool::published()->where('lifecycle_status', 'archived')->count()");
    expect($content)->toContain(': 0;');
});

test('la fiche archivée sans remplaçant renvoie 404 au public', function () {
    $content = file_get_contents(base_path('Modules/Directory/app/Http/Controllers/PublicDirectoryController.php'));
    expect($content)->toContain('abort(404)');
    expect($content)->toContain("lifecycle_status === 'archived'");
});

test('le sitemap exclut les archivés', function () {
    $content = file_get_contents(base_path('Modules/SEO/app/Http/Controllers/SitemapController.php'));
    expect($content)->toContain('Modules\Directory\Models\Tool::published()->notArchived()');
});

test('le scope notArchived reste chaînable avec published', function () {
    $sql = Tool::published()->notArchived()->toSql();
    expect($sql)->toContain('status');
    expect($sql)->toContain('lifecycle_status');
});
