<?php

declare(strict_types=1);

/**
 * Tests du module « vérification » étendu au blogue (2026-08-31, demande fondateur : « aussi
 * avoir des tags qui disent si on contredit une nouvelle qui circule sur internet »).
 *
 * Couvre les quatre points d'entrée du module côté blogue :
 *   A. le modèle (Modules\Blog\Models\ArticleVerification : contrat partagé avec
 *      NewsArticle::FACT_CHECK_VERDICTS, hasFactCheck(), factCheckVerdict(),
 *      hasFactCheckInconclusive(), accesseurs de compatibilité) et la relation
 *      Modules\Blog\Models\Article::verifications() ;
 *   B/C. la porte d'écriture blog:verify --payload (Modules\Blog\Console\ArticleVerifyCommand) -
 *      création, mise à jour, retrait ;
 *   D. le rendu public (Modules\Blog\resources\views\components\article-verifications.blade.php,
 *      inclus dans fronttheme::blog.show) - strictement additif, liste jamais un verdict global,
 *      filtre de sécurité sur les sources.
 *
 * Régression volontairement ABSENTE de ce fichier sur le module des actualités : voir
 * Modules/News/tests/Feature/FactCheckModuleTest.php, qui reste la suite de référence pour le
 * vocabulaire partagé - les deux suites doivent rester vertes ensemble après toute modification
 * de NewsArticle::FACT_CHECK_VERDICTS.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\ArticleVerification;

uses(Tests\TestCase::class, RefreshDatabase::class);

function bvPayloadFile(array $data): string
{
    $path = sys_get_temp_dir().'/bv-test-'.uniqid().'.json';
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $path;
}

// ── A. Modèle : relation, contrat partagé avec NewsArticle ─────────────────────────────

it('verifications() est vide par défaut', function () {
    $article = Article::factory()->create();

    expect($article->verifications->isEmpty())->toBeTrue();
});

it('verifications() est ordonnée par position', function () {
    $article = Article::factory()->create();

    ArticleVerification::create(['article_id' => $article->id, 'claim' => 'Claim 1', 'verdict' => 'citation_inexacte', 'position' => 2]);
    ArticleVerification::create(['article_id' => $article->id, 'claim' => 'Claim 2', 'verdict' => 'citation_inexacte', 'position' => 0]);
    ArticleVerification::create(['article_id' => $article->id, 'claim' => 'Claim 3', 'verdict' => 'citation_inexacte', 'position' => 1]);

    expect($article->verifications->pluck('position')->all())->toBe([0, 1, 2]);
});

it('hasFactCheck est vrai pour un verdict connu, faux pour un verdict retiré du vocabulaire', function () {
    $article = Article::factory()->create();

    $valid = ArticleVerification::create(['article_id' => $article->id, 'claim' => 'Valid', 'verdict' => 'citation_inexacte']);
    $invalid = ArticleVerification::create(['article_id' => $article->id, 'claim' => 'Invalid', 'verdict' => 'verdict-invente-retire']);

    expect($valid->hasFactCheck())->toBeTrue()
        ->and($invalid->hasFactCheck())->toBeFalse();
});

it('factCheckVerdict renvoie le libellé et la teinte du vocabulaire partagé', function () {
    $article = Article::factory()->create();
    $verification = ArticleVerification::create([
        'article_id' => $article->id,
        'claim' => 'Test',
        'verdict' => 'contenu_synthetique',
    ]);

    $result = $verification->factCheckVerdict();

    expect($result['label'])->toContain('IA')
        ->and($result['tone'])->toBeString()->not->toBeEmpty();
});

it('hasFactCheckInconclusive est vrai sans verdict avec inconclusive_at posé, faux si un verdict existe', function () {
    $article = Article::factory()->create();

    $inconclusive = ArticleVerification::create([
        'article_id' => $article->id,
        'claim' => 'Inconclusive',
        'verdict' => null,
        'inconclusive_at' => now(),
    ]);

    $conclusive = ArticleVerification::create([
        'article_id' => $article->id,
        'claim' => 'Conclusive',
        'verdict' => 'citation_inexacte',
        'inconclusive_at' => now(),
    ]);

    expect($inconclusive->hasFactCheckInconclusive())->toBeTrue()
        ->and($conclusive->hasFactCheckInconclusive())->toBeFalse();
});

it('les accesseurs fact_check_claim et fact_check_source pointent vers claim et source_url', function () {
    $article = Article::factory()->create();
    $verification = ArticleVerification::create([
        'article_id' => $article->id,
        'claim' => 'Une affirmation.',
        'source_url' => 'https://exemple.com/x',
    ]);

    expect($verification->fact_check_claim)->toBe('Une affirmation.')
        ->and($verification->fact_check_source)->toBe('https://exemple.com/x');
});

// ── B. Porte d'écriture blog:verify - création ──────────────────────────────────────────

it('crée une vérification avec un verdict valide', function () {
    $article = Article::factory()->create();
    $path = bvPayloadFile([
        'verification' => [
            'claim' => 'Affirmation test',
            'verdict' => 'citation_inexacte',
            'source_url' => 'https://example.com/test',
        ],
    ]);

    $this->artisan('blog:verify', ['article' => $article->id, '--payload' => $path])
        ->assertSuccessful();

    $verification = ArticleVerification::where('article_id', $article->id)->first();
    expect($verification)->not->toBeNull()
        ->and($verification->claim)->toBe('Affirmation test')
        ->and($verification->verdict)->toBe('citation_inexacte')
        ->and($verification->source_url)->toBe('https://example.com/test');
});

it('refuse un article introuvable', function () {
    $path = bvPayloadFile([
        'verification' => [
            'claim' => 'Test',
            'verdict' => 'citation_inexacte',
        ],
    ]);

    $this->artisan('blog:verify', ['article' => 999999, '--payload' => $path])
        ->assertFailed();
});

it('refuse sans --payload', function () {
    $article = Article::factory()->create();

    $this->artisan('blog:verify', ['article' => $article->id])
        ->assertFailed();
});

it('refuse un JSON invalide', function () {
    $article = Article::factory()->create();
    $path = sys_get_temp_dir().'/bv-invalid-'.uniqid().'.json';
    file_put_contents($path, "ceci n'est pas du json");

    $this->artisan('blog:verify', ['article' => $article->id, '--payload' => $path])
        ->assertFailed();
});

it('refuse un payload sans la clé verification', function () {
    $article = Article::factory()->create();
    $path = bvPayloadFile(['autre_chose' => true]);

    $this->artisan('blog:verify', ['article' => $article->id, '--payload' => $path])
        ->assertFailed();
});

it('refuse une sous-clé inconnue', function () {
    $article = Article::factory()->create();
    $path = bvPayloadFile([
        'verification' => [
            'claim' => 'Test',
            'verdict' => 'citation_inexacte',
            'bogus' => 'invalid',
        ],
    ]);

    $this->artisan('blog:verify', ['article' => $article->id, '--payload' => $path])
        ->assertFailed();
});

it('crée une vérification non concluante, sans verdict', function () {
    $article = Article::factory()->create();
    $path = bvPayloadFile([
        'verification' => [
            'claim' => 'Non concluant',
            'inconclusive' => true,
        ],
    ]);

    $this->artisan('blog:verify', ['article' => $article->id, '--payload' => $path])
        ->assertSuccessful();

    $verification = ArticleVerification::where('article_id', $article->id)->first();
    expect($verification)->not->toBeNull()
        ->and($verification->verdict)->toBeNull()
        ->and($verification->inconclusive_at)->not->toBeNull();
});

it('refuse inconclusive et verdict ensemble', function () {
    $article = Article::factory()->create();
    $path = bvPayloadFile([
        'verification' => [
            'claim' => 'Mixte',
            'verdict' => 'citation_inexacte',
            'inconclusive' => true,
        ],
    ]);

    $this->artisan('blog:verify', ['article' => $article->id, '--payload' => $path])
        ->assertFailed();

    expect(ArticleVerification::count())->toBe(0);
});

it('refuse un verdict hors du vocabulaire', function () {
    $article = Article::factory()->create();
    $path = bvPayloadFile([
        'verification' => [
            'claim' => 'Hors vocabulaire',
            'verdict' => 'verdict-invente',
        ],
    ]);

    $this->artisan('blog:verify', ['article' => $article->id, '--payload' => $path])
        ->assertFailed();
});

it('refuse une source_url qui n\'est pas http(s)', function () {
    $article = Article::factory()->create();
    $path = bvPayloadFile([
        'verification' => [
            'claim' => 'URL invalide',
            'verdict' => 'citation_inexacte',
            'source_url' => 'javascript:alert(1)',
        ],
    ]);

    $this->artisan('blog:verify', ['article' => $article->id, '--payload' => $path])
        ->assertFailed();
});

it('refuse plus de 5 sources', function () {
    $article = Article::factory()->create();
    $path = bvPayloadFile([
        'verification' => [
            'claim' => 'Trop de sources',
            'verdict' => 'citation_inexacte',
            'sources' => [
                'https://example.com/1',
                'https://example.com/2',
                'https://example.com/3',
                'https://example.com/4',
                'https://example.com/5',
                'https://example.com/6',
            ],
        ],
    ]);

    $this->artisan('blog:verify', ['article' => $article->id, '--payload' => $path])
        ->assertFailed();
});

it('refuse verified_at explicitement null à la création', function () {
    $article = Article::factory()->create();
    $path = bvPayloadFile([
        'verification' => [
            'claim' => 'Null verified_at',
            'verdict' => 'citation_inexacte',
            'verified_at' => null,
        ],
    ]);

    $this->artisan('blog:verify', ['article' => $article->id, '--payload' => $path])
        ->assertFailed();
});

it('verified_at par défaut à la création si absent', function () {
    $article = Article::factory()->create();
    $path = bvPayloadFile([
        'verification' => [
            'claim' => 'Sans verified_at',
            'verdict' => 'citation_inexacte',
        ],
    ]);

    $this->artisan('blog:verify', ['article' => $article->id, '--payload' => $path])
        ->assertSuccessful();

    $verification = ArticleVerification::where('article_id', $article->id)->first();
    expect($verification)->not->toBeNull()
        ->and($verification->verified_at)->not->toBeNull();
});

// ── C. Porte d'écriture blog:verify - mise à jour et retrait ────────────────────────────

it('met à jour une vérification existante par id', function () {
    $article = Article::factory()->create();
    $existing = ArticleVerification::create([
        'article_id' => $article->id,
        'claim' => 'Ancien',
        'verdict' => 'citation_inexacte',
    ]);

    $path = bvPayloadFile([
        'verification' => [
            'id' => $existing->id,
            'claim' => 'Nouveau',
            'verdict' => 'attribution_erronee',
        ],
    ]);

    $this->artisan('blog:verify', ['article' => $article->id, '--payload' => $path])
        ->assertSuccessful();

    $updated = ArticleVerification::find($existing->id);
    expect($updated->claim)->toBe('Nouveau')
        ->and($updated->verdict)->toBe('attribution_erronee');
});

it('refuse de mettre à jour l\'id d\'un AUTRE article', function () {
    $articleA = Article::factory()->create();
    $articleB = Article::factory()->create();

    $verificationA = ArticleVerification::create([
        'article_id' => $articleA->id,
        'claim' => 'Original A',
        'verdict' => 'citation_inexacte',
    ]);

    $path = bvPayloadFile([
        'verification' => [
            'id' => $verificationA->id,
            'claim' => 'Modifié via B',
            'verdict' => 'attribution_erronee',
        ],
    ]);

    $this->artisan('blog:verify', ['article' => $articleB->id, '--payload' => $path])
        ->assertFailed();

    $unchanged = ArticleVerification::find($verificationA->id);
    expect($unchanged->claim)->toBe('Original A');
});

it('une clé optionnelle absente à la mise à jour ne touche pas la valeur existante', function () {
    $article = Article::factory()->create();
    $existing = ArticleVerification::create([
        'article_id' => $article->id,
        'claim' => 'Test',
        'verdict' => 'citation_inexacte',
        'motif' => 'Motif original.',
    ]);

    $path = bvPayloadFile([
        'verification' => [
            'id' => $existing->id,
            'claim' => 'Même motif',
            'verdict' => 'attribution_erronee',
        ],
    ]);

    $this->artisan('blog:verify', ['article' => $article->id, '--payload' => $path])
        ->assertSuccessful();

    $updated = ArticleVerification::find($existing->id);
    expect($updated->motif)->toBe('Motif original.');
});

it('retire (suppression douce) une vérification par id', function () {
    $article = Article::factory()->create();
    $existing = ArticleVerification::create([
        'article_id' => $article->id,
        'claim' => 'À supprimer',
        'verdict' => 'citation_inexacte',
    ]);

    $path = bvPayloadFile([
        'verification' => [
            'id' => $existing->id,
            'remove' => true,
        ],
    ]);

    $this->artisan('blog:verify', ['article' => $article->id, '--payload' => $path])
        ->assertSuccessful();

    expect(ArticleVerification::find($existing->id))->toBeNull()
        ->and(ArticleVerification::withTrashed()->find($existing->id))->not->toBeNull();
});

// ── D. Rendu public - strictement additif, liste, sécurité ──────────────────────────────

it('un article sans vérification reste strictement inchangé, une seule n\'affiche pas de titre, deux en affichent un', function () {
    // Étape (a) : aucune vérification - strictement inchangé.
    $article = Article::factory()->published()->create(['slug' => 'article-test-verifications', 'title' => 'Article test vérifications']);
    $response = $this->get('/blog/'.$article->slug);
    $response->assertOk();
    $response->assertDontSee('bl-verifications', false);

    // Étape (b) : une seule vérification - badge visible, aucun titre de section.
    ArticleVerification::create([
        'article_id' => $article->id,
        'claim' => 'Première vérification',
        'verdict' => 'citation_inexacte',
    ]);

    // Le sélecteur CSS ".bl-verifications__heading" est poussé dans <style> dès qu'IL EXISTE au
    // moins une vérification (la règle CSS existe même quand l'élément ne se rend pas) - vérifier
    // la BALISE réelle, jamais le seul nom de classe en sous-chaîne, sous peine de faux positif.
    $response = $this->get('/blog/'.$article->slug);
    $response->assertOk();
    $response->assertSee('Citation inexacte')
        ->assertDontSee('<h2 class="bl-verifications__heading">', false);

    // Étape (c) : deux vérifications - titre de section « Vérifications » affiché.
    ArticleVerification::create([
        'article_id' => $article->id,
        'claim' => 'Deuxième vérification',
        'verdict' => 'attribution_erronee',
    ]);

    $response = $this->get('/blog/'.$article->slug);
    $response->assertOk();
    $response->assertSee('<h2 class="bl-verifications__heading">', false)
        ->assertSee('Vérifications')
        ->assertSee('Citation inexacte')
        ->assertSee('Attribution erronée');
});

it('filtre une source non http(s) au rendu public', function () {
    $article = Article::factory()->published()->create(['slug' => 'article-test-source-filtree', 'title' => 'Article test source filtrée']);

    ArticleVerification::create([
        'article_id' => $article->id,
        'claim' => 'Vérification avec sources mixtes',
        'verdict' => 'citation_inexacte',
        'sources' => ['https://exemple.com/preuve-valide', 'javascript:alert(1)'],
    ]);

    $response = $this->get('/blog/'.$article->slug);

    $response->assertOk()
        ->assertSee('exemple.com/preuve-valide', false)
        ->assertDontSee('javascript:alert', false);
});
