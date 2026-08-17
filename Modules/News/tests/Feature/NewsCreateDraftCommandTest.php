<?php

declare(strict_types=1);

/**
 * Tests de la commande news:create-draft - création manuelle d'une fiche brouillon à partir d'un
 * lien (design doc "Actus - composition manuelle assistée" 2026-08-15, section "Améliorations en
 * attente", point 1). Couvre : la création, l'idempotence par URL (NewsArticle::
 * createManualDraft(), SEULE implémentation - DRY strict, réutilisée telle quelle par
 * Modules\News\Http\Controllers\Admin\NewsCompositionController::createDraft(), voir
 * Modules/News/tests/Feature/NewsCompositionBuilderTest.php pour la porte web équivalente), le
 * titre par défaut, le titre explicite via --title, l'URL invalide, et la forme JSON exacte de
 * sortie (id, slug, url, created, mini_prompt).
 *
 * NON EXÉCUTÉS par ce sous-agent (consigne docs/CONTRAINTES-SOUS-AGENTS.md, section 2) - à
 * exécuter par le superviseur, une seule suite à la fois.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Création + idempotence ────────────────────────────────────────────────────────────

it('news:create-draft creates a draft fiche with the "Soumission manuelle" source and the default title', function () {
    $url = 'https://exemple-editeur.com/ncd-'.uniqid();

    Artisan::call('news:create-draft', ['url' => $url]);
    $decoded = json_decode(trim(Artisan::output()), true);

    expect($decoded)->not->toBeNull()
        ->and($decoded['created'])->toBeTrue()
        ->and($decoded['url'])->toBe($url)
        ->and($decoded['mini_prompt'])->toBe('/actu2 '.$url.' fiche:'.$decoded['id']);

    $article = NewsArticle::find($decoded['id']);
    expect($article)->not->toBeNull()
        ->and($article->title)->toBe('Fiche créée depuis un lien - à composer')
        ->and($article->slug)->toBe($decoded['slug'])
        ->and($article->is_published)->toBeFalse()
        ->and($article->seo_status)->toBe('index')
        ->and($article->guid)->toContain('manuel-');

    $source = $article->source;
    expect($source)->not->toBeNull()
        ->and($source->name)->toBe('Soumission manuelle')
        ->and($source->url)->toBe('manuel://soumission-directe')
        ->and($source->active)->toBeFalse();
});

it('news:create-draft uses the --title option when given', function () {
    $url = 'https://exemple-editeur.com/ncd-titre-'.uniqid();

    Artisan::call('news:create-draft', ['url' => $url, '--title' => 'Un titre de travail explicite']);
    $decoded = json_decode(trim(Artisan::output()), true);

    $article = NewsArticle::find($decoded['id']);
    expect($article->title)->toBe('Un titre de travail explicite');
});

it('news:create-draft is idempotent by URL - a second call returns the same fiche, created:false, no duplicate', function () {
    $url = 'https://exemple-editeur.com/ncd-idem-'.uniqid();

    Artisan::call('news:create-draft', ['url' => $url]);
    $first = json_decode(trim(Artisan::output()), true);

    Artisan::call('news:create-draft', ['url' => $url, '--title' => 'Titre ignoré au second appel']);
    $second = json_decode(trim(Artisan::output()), true);

    expect($second['created'])->toBeFalse()
        ->and($second['id'])->toBe($first['id'])
        ->and($second['slug'])->toBe($first['slug']);

    expect(NewsArticle::where('url', $url)->count())->toBe(1);
    // Le titre du premier appel n'est jamais écrasé par un second appel idempotent.
    expect(NewsArticle::find($first['id'])->title)->not->toBe('Titre ignoré au second appel');
});

it('news:create-draft reuses the same "Soumission manuelle" source across several distinct URLs', function () {
    Artisan::call('news:create-draft', ['url' => 'https://exemple-editeur.com/ncd-a-'.uniqid()]);
    $a = json_decode(trim(Artisan::output()), true);

    Artisan::call('news:create-draft', ['url' => 'https://exemple-editeur.com/ncd-b-'.uniqid()]);
    $b = json_decode(trim(Artisan::output()), true);

    $sourceIdA = NewsArticle::find($a['id'])->news_source_id;
    $sourceIdB = NewsArticle::find($b['id'])->news_source_id;
    expect($sourceIdA)->toBe($sourceIdB);
    expect(NewsSource::where('url', 'manuel://soumission-directe')->count())->toBe(1);
});

// ── Accepte un lien x.com/twitter.com tel quel ────────────────────────────────────────

it('news:create-draft accepts an x.com URL as-is, no special-casing', function () {
    $url = 'https://x.com/exemple/status/9876543210';

    Artisan::call('news:create-draft', ['url' => $url]);
    $decoded = json_decode(trim(Artisan::output()), true);

    expect($decoded['created'])->toBeTrue()
        ->and($decoded['url'])->toBe($url);
});

// ── URL invalide ───────────────────────────────────────────────────────────────────────

it('news:create-draft refuses an empty or malformed URL, nothing is created', function () {
    $countBefore = NewsArticle::count();

    $this->artisan('news:create-draft', ['url' => 'pas-une-url'])->assertFailed();
    $this->artisan('news:create-draft', ['url' => ''])->assertFailed();

    expect(NewsArticle::count())->toBe($countBefore);
});

// ── Journalisation (canal dédié 'composition') ────────────────────────────────────────

it('news:create-draft writes to the dedicated composition log file', function () {
    $logPath = storage_path('logs/composition-'.now()->format('Y-m-d').'.log');
    @unlink($logPath);

    $url = 'https://exemple-editeur.com/ncd-log-'.uniqid();
    $this->artisan('news:create-draft', ['url' => $url])->assertSuccessful();

    expect(file_exists($logPath))->toBeTrue();
    $content = file_get_contents($logPath);
    expect($content)->toContain('news:create-draft');

    @unlink($logPath);
});
