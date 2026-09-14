<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * La date de relecture doit être VRAIE : une date fabriquée se retourne contre le site, et c'est
 * précisément le champ dont l'honnêteté fait l'argument. Ces tests verrouillent trois choses que
 * la lecture du code ne rend pas évidentes : on copie content_updated_at (jamais now()), on
 * n'invente aucune date quand elle manque, et on ne réécrit jamais une valeur existante.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

function braSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source relecture', 'url' => 'https://bra.exemple.com/rss',
        'language' => 'fr', 'active' => true,
    ]);
}

function braFiche(int $src, array $o = []): NewsArticle
{
    static $i = 0;
    $i++;
    $s = $i.'-'.uniqid();

    // `content_updated_at` et `reviewed_at` ne sont PAS dans $fillable : create() les ignore
    // en silence. On les sort donc du lot et on les ecrit directement, sinon le test
    // mesurerait une valeur qu'il n'a jamais reussi a poser - un faux vert garanti.
    $directs = array_intersect_key($o, array_flip(['content_updated_at', 'reviewed_at', 'reviewed_by']));
    $o = array_diff_key($o, $directs);

    $fiche = NewsArticle::create(array_merge([
        'news_source_id' => $src, 'title' => "Fiche {$i}", 'guid' => "g-bra-{$s}",
        'url' => "https://exemple.com/bra-{$s}", 'slug' => "bra-{$s}",
        'pub_date' => now()->subDays(30), 'is_published' => true, 'seo_status' => 'index',
    ], $o));

    // Toujours ecrire la cle, meme a null : sinon un defaut du modele la remplirait.
    \Illuminate\Support\Facades\DB::table('news_articles')->where('id', $fiche->id)->update([
        'content_updated_at' => $directs['content_updated_at'] ?? null,
        'reviewed_at' => $directs['reviewed_at'] ?? null,
        'reviewed_by' => $directs['reviewed_by'] ?? null,
    ]);

    return $fiche->fresh();
}

it('pose la VRAIE date de relecture, celle du contenu, jamais maintenant', function () {
    $src = braSource();
    $quand = now()->subDays(26)->startOfSecond();

    $f = braFiche($src->id, [
        'editorial_proof_pairs' => [['statement' => 'A', 'excerpt' => 'B', 'type' => 'fact']],
        'content_updated_at' => $quand,
    ]);

    $this->artisan('news:backfill-reviewed-at --appliquer')->assertSuccessful();

    $f->refresh();
    expect($f->reviewed_at)->not->toBeNull();
    expect($f->reviewed_at->format('Y-m-d H:i:s'))->toBe($quand->format('Y-m-d H:i:s'));
    // Le piège que ce test existe pour attraper : une date du jour serait un mensonge.
    expect($f->reviewed_at->isToday())->toBeFalse();
    expect($f->reviewed_by)->toBe('la rédaction de laveille.ai');
});

it("n'invente AUCUNE date quand le contenu n'en porte pas", function () {
    $src = braSource();
    $f = braFiche($src->id, [
        'editorial_proof_pairs' => [['statement' => 'A', 'excerpt' => 'B', 'type' => 'fact']],
        'content_updated_at' => null,
    ]);

    $this->artisan('news:backfill-reviewed-at --appliquer')->assertSuccessful();

    expect($f->fresh()->reviewed_at)->toBeNull();
});

it('ignore les fiches SANS paire de preuve : elles n\'ont pas été relues', function () {
    $src = braSource();
    $f = braFiche($src->id, ['editorial_proof_pairs' => null, 'content_updated_at' => now()->subDay()]);

    $this->artisan('news:backfill-reviewed-at --appliquer')->assertSuccessful();

    expect($f->fresh()->reviewed_at)->toBeNull();
});

it('ne réécrit JAMAIS une date déjà posée (idempotence)', function () {
    $src = braSource();
    $ancienne = now()->subDays(60)->startOfSecond();

    $f = braFiche($src->id, [
        'editorial_proof_pairs' => [['statement' => 'A', 'excerpt' => 'B', 'type' => 'fact']],
        'content_updated_at' => now()->subDays(5),
        'reviewed_at' => $ancienne,
    ]);

    $this->artisan('news:backfill-reviewed-at --appliquer')->assertSuccessful();

    expect($f->fresh()->reviewed_at->format('Y-m-d H:i:s'))->toBe($ancienne->format('Y-m-d H:i:s'));
});

it("n'écrit rien sans --appliquer (la simulation est le défaut)", function () {
    $src = braSource();
    $f = braFiche($src->id, [
        'editorial_proof_pairs' => [['statement' => 'A', 'excerpt' => 'B', 'type' => 'fact']],
        'content_updated_at' => now()->subDays(3),
    ]);

    $this->artisan('news:backfill-reviewed-at')->assertSuccessful();

    expect($f->fresh()->reviewed_at)->toBeNull();
});

it('ne touche pas updated_at : dater une relecture ne modifie pas le contenu', function () {
    $src = braSource();
    $f = braFiche($src->id, [
        'editorial_proof_pairs' => [['statement' => 'A', 'excerpt' => 'B', 'type' => 'fact']],
        'content_updated_at' => now()->subDays(4),
    ]);
    $avant = $f->fresh()->updated_at->format('Y-m-d H:i:s');

    $this->artisan('news:backfill-reviewed-at --appliquer')->assertSuccessful();

    expect($f->fresh()->updated_at->format('Y-m-d H:i:s'))->toBe($avant);
});
