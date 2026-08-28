<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest - commande news:backfill-auto-tools, mode simulation (--dry-run).
 *
 * Pourquoi ce test existe : avant d'écrire quoi que ce soit sur 4500 fiches publiées, il
 * faut pouvoir MESURER combien d'entre elles mentionnent réellement un outil de l'annuaire.
 * Une fiche sans outil lié n'est pas forcément un défaut - une actualité sur une politique
 * publique n'a aucune raison d'en mentionner un. Le mode simulation sépare ces deux
 * populations sans toucher à la base.
 *
 * Le second test verrouille la contrepartie : hors simulation, la commande attache
 * réellement. Si l'on inversait la condition, il passerait au rouge.
 *
 * Helpers préfixés `bfat` (BackFill Auto Tools) pour éviter les redéclarations globales
 * avec les autres suites du module News.
 */

use Illuminate\Support\Facades\DB;
use Modules\Directory\Models\Tool;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

function bfatSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source BFAT',
        'url' => 'https://bfat-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function bfatTool(string $name, string $slug): Tool
{
    return Tool::withoutEvents(fn () => Tool::create([
        'name' => ['fr_CA' => $name, 'en' => $name],
        'slug' => ['fr_CA' => $slug, 'en' => $slug],
        'status' => 'published',
        'pricing' => 'free',
    ]));
}

function bfatArticle(int $sourceId, string $summary): NewsArticle
{
    $suffix = uniqid();

    return NewsArticle::withoutEvents(fn () => NewsArticle::create([
        'news_source_id' => $sourceId,
        'title' => 'Article BFAT',
        'guid' => "guid-bfat-{$suffix}",
        'url' => "https://exemple.com/bfat-{$suffix}",
        'description' => '',
        'summary' => $summary,
        'slug' => "article-bfat-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ]));
}

function bfatLiaisons(int $articleId): int
{
    return DB::table('news_article_tool')->where('news_article_id', $articleId)->count();
}

// ── Mode simulation ───────────────────────────────────────────────────────────

it('le mode simulation nannonce aucune ecriture et nen fait aucune', function () {
    $source = bfatSource();
    bfatTool('Zorglubulator', 'zorglubulator');

    // Une fiche qui mentionne l'outil : réparable.
    $reparable = bfatArticle($source->id, 'Le nouvel outil Zorglubulator change la donne.');
    // Une fiche qui n'en mentionne aucun : son absence de lien n'est PAS un défaut.
    $normale = bfatArticle($source->id, 'Le gouvernement dépose un projet de loi sur la vie privée.');

    expect(bfatLiaisons($reparable->id))->toBe(0);
    expect(bfatLiaisons($normale->id))->toBe(0);

    $this->artisan('news:backfill-auto-tools', ['--limit' => 50, '--dry-run' => true])
        ->assertExitCode(0);

    // La base n'a pas bougé : c'est tout l'intérêt du mode simulation.
    expect(bfatLiaisons($reparable->id))->toBe(0);
    expect(bfatLiaisons($normale->id))->toBe(0);
});

// ── Mode réel (contrepartie : le test ci-dessus ne prouve rien seul) ───────────

it('hors simulation la commande attache reellement loutil mentionne', function () {
    $source = bfatSource();
    bfatTool('Zorglubulator', 'zorglubulator');

    $reparable = bfatArticle($source->id, 'Le nouvel outil Zorglubulator change la donne.');
    $normale = bfatArticle($source->id, 'Le gouvernement dépose un projet de loi sur la vie privée.');

    $this->artisan('news:backfill-auto-tools', ['--limit' => 50])
        ->assertExitCode(0);

    expect(bfatLiaisons($reparable->id))->toBe(1);
    // La fiche sans mention ne reçoit rien : la commande n'invente aucun rattachement.
    expect(bfatLiaisons($normale->id))->toBe(0);

    $pivot = DB::table('news_article_tool')->where('news_article_id', $reparable->id)->first();
    expect($pivot->source)->toBe('auto');
});

// ── Tirage au hasard (reserve a la simulation) ─────────────────────────────────

it('le tirage au hasard reste une simulation et nécrit rien', function () {
    $source = bfatSource();
    bfatTool('Zorglubulator', 'zorglubulator');

    $a = bfatArticle($source->id, 'Le nouvel outil Zorglubulator change la donne.');
    $b = bfatArticle($source->id, 'Un autre texte qui cite Zorglubulator lui aussi.');

    $this->artisan('news:backfill-auto-tools', [
        '--limit' => 50,
        '--dry-run' => true,
        '--echantillon' => true,
    ])->assertExitCode(0);

    // La propriete qui compte vraiment : le tirage au hasard ne doit RIEN ecrire.
    // Le caractere aleatoire de l'ordre lui-meme n'est pas verifie ici : il est delegue
    // a inRandomOrder() du framework, et un test qui tire deux fois pourrait tomber
    // deux fois sur la meme fiche sans que rien ne soit cassé.
    expect(bfatLiaisons($a->id))->toBe(0);
    expect(bfatLiaisons($b->id))->toBe(0);
});
