<?php

declare(strict_types=1);

/**
 * Lot 4a du design doc "extension de l'écran de composition des actualités" (2026-09-03,
 * sections 2.5, 2.6, 2.7) : NewsCompositionController::update() accepte désormais 'title',
 * 'image_credit', 'nature_original' et 'niveau_preuve' - mêmes bornes et mêmes listes de valeurs
 * que la porte de l'agent (NewsApplyCommand --payload) - sous un verrou optimiste proportionné
 * ('expected_updated_at', actif seulement si le payload porte au moins une clé riche).
 * storeProofPair() délègue désormais à CompositionPayloadNormalizer::validateProofPair() (Lot 1,
 * v1.248.2) au lieu d'inliner sa propre revalidation.
 *
 * Hors périmètre de ce fichier (Lot 4b, son propre chantier) : composed_summary, primary_sources,
 * related_tool_slugs.
 *
 * Piège d'échappement Blade (même note que NewsAdminImageUploadUnifiedTest/
 * NewsProvenanceTwoSourcesTest) : aucune apostrophe dans les chaînes attendues côté HTML - elle y
 * est rendue &#039;, jamais l'apostrophe droite.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Helpers préfixés crf (Composition Rich Fields) - Pest charge tous les fichiers dans un seul
// processus, un nom nu entrerait en collision avec un autre fichier de test.
function crfSource(): NewsSource
{
    static $i = 0;
    $i++;

    return NewsSource::create([
        'name' => 'Source champs riches',
        'url' => 'https://crf-source.exemple.com/rss-'.$i.'-'.uniqid(),
        'language' => 'fr',
        'active' => true,
    ]);
}

function crfArticle(array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => crfSource()->id,
        'title' => "Article champs riches {$i}",
        'guid' => "guid-crf-{$suffix}",
        'url' => "https://exemple.com/crf-{$suffix}",
        'description' => '',
        'summary' => "Resume initial {$i}",
        'slug' => "article-crf-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));
}

/**
 * Administrateur capable d'atteindre les routes (auth + two.factor + EnsureIsAdmin). Même
 * fabrication que NewsCompositionBuilderTest::ncbAdmin() / NewsAdminImageUploadUnifiedTest::
 * naiAdmin() : le rôle passe par Spatie, la table users n'a PAS de colonne role.
 */
function crfAdmin(): \App\Models\User
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

/**
 * Jeton de verrou optimiste (design doc 2026-09-03, section 2.6) - un payload qui porte au
 * moins un champ riche doit fournir 'expected_updated_at' pour réussir. Recharge TOUJOURS depuis
 * la base (jamais l'instance en mémoire) : l'updated_at courant est ce que le serveur compare.
 */
function crfToken(NewsArticle $article): string
{
    return $article->fresh()->updated_at->toIso8601String();
}

// ── title (design doc section 2.5) ──────────────────────────────────────────────

it('update() writes title and regenerates the slug on a DRAFT article', function () {
    $admin = crfAdmin();
    $article = crfArticle(['is_published' => false, 'title' => 'Ancien titre', 'slug' => 'ancien-slug-crf']);
    $oldSlug = $article->slug;

    $response = $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'title' => 'Nouveau titre du brouillon',
        'expected_updated_at' => crfToken($article),
    ]);

    $response->assertOk();
    $fresh = $article->fresh();
    expect($fresh->title)->toBe('Nouveau titre du brouillon')
        ->and($fresh->slug)->not->toBe($oldSlug)
        ->and($fresh->slug)->toContain('nouveau-titre-du-brouillon');
});

it('update() writes title but NEVER moves the slug on an ALREADY PUBLISHED article', function () {
    $admin = crfAdmin();
    $article = crfArticle(['is_published' => true, 'title' => 'Titre publie original', 'slug' => 'slug-fige-crf']);

    $response = $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'title' => 'Titre corrige apres publication',
        'expected_updated_at' => crfToken($article),
    ]);

    $response->assertOk();
    $fresh = $article->fresh();
    expect($fresh->title)->toBe('Titre corrige apres publication')
        ->and($fresh->slug)->toBe('slug-fige-crf');
});

it('update() refuses an empty or null title with 422, writes nothing', function () {
    $admin = crfAdmin();
    $article = crfArticle(['title' => 'Titre initial intact']);

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'title' => '   ',
        'expected_updated_at' => crfToken($article),
    ])->assertStatus(422);
    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'title' => null,
        'expected_updated_at' => crfToken($article),
    ])->assertStatus(422);

    expect($article->fresh()->title)->toBe('Titre initial intact');
});

// ── image_credit (design doc section 2.5) ───────────────────────────────────────

it('update() writes and then clears image_credit', function () {
    $admin = crfAdmin();
    $article = crfArticle(['image_credit' => null]);

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'image_credit' => 'Photo : Jean Untel, Agence Untelle',
        'expected_updated_at' => crfToken($article),
    ])->assertOk();
    expect($article->fresh()->image_credit)->toBe('Photo : Jean Untel, Agence Untelle');

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'image_credit' => null,
        'expected_updated_at' => crfToken($article),
    ])->assertOk();
    expect($article->fresh()->image_credit)->toBeNull();
});

// ── nature_original / niveau_preuve, contre la liste unique du modele (design doc 2.5) ──

it('update() writes a valid nature_original and rejects an unknown one with 422', function () {
    $admin = crfAdmin();
    $article = crfArticle(['nature_original' => null]);
    $valid = array_key_first(NewsArticle::NATURE_ORIGINAL_VALUES);

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'nature_original' => $valid,
        'expected_updated_at' => crfToken($article),
    ])->assertOk();
    expect($article->fresh()->nature_original)->toBe($valid);

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'nature_original' => 'valeur-qui-n-existe-pas',
        'expected_updated_at' => crfToken($article),
    ])->assertStatus(422);
    expect($article->fresh()->nature_original)->toBe($valid);
});

it('update() writes a valid niveau_preuve and rejects an unknown one with 422', function () {
    $admin = crfAdmin();
    $article = crfArticle(['niveau_preuve' => null]);
    $valid = array_key_first(NewsArticle::NIVEAU_PREUVE_VALUES);

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'niveau_preuve' => $valid,
        'expected_updated_at' => crfToken($article),
    ])->assertOk();
    expect($article->fresh()->niveau_preuve)->toBe($valid);

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'niveau_preuve' => 'valeur-qui-n-existe-pas',
        'expected_updated_at' => crfToken($article),
    ])->assertStatus(422);
    expect($article->fresh()->niveau_preuve)->toBe($valid);
});

// ── verrou optimiste (design doc section 2.6) ────────────────────────────────────

it('update() with only historical fields ignores a stale expected_updated_at - zero regression', function () {
    $admin = crfAdmin();
    $article = crfArticle(['summary' => 'Ancien resume']);

    $response = $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'summary' => 'Nouveau resume, sans aucune cle riche dans ce payload',
        'expected_updated_at' => '2000-01-01T00:00:00-05:00',
    ]);

    $response->assertOk();
    expect($article->fresh()->summary)->toBe('Nouveau resume, sans aucune cle riche dans ce payload');
});

it('update() with a rich field and a stale expected_updated_at is refused with 409, writes nothing', function () {
    $admin = crfAdmin();
    $article = crfArticle(['image_credit' => 'Credit original']);

    $response = $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'image_credit' => 'Credit qui ne doit jamais s ecrire',
        'expected_updated_at' => '2000-01-01T00:00:00-05:00',
    ]);

    $response->assertStatus(409);
    expect($article->fresh()->image_credit)->toBe('Credit original');
});

it('update() with a rich field and NO expected_updated_at at all is refused with 409', function () {
    $admin = crfAdmin();
    $article = crfArticle(['image_credit' => 'Credit original']);

    $response = $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'image_credit' => 'Credit qui ne doit jamais s ecrire non plus',
    ]);

    $response->assertStatus(409);
    expect($article->fresh()->image_credit)->toBe('Credit original');
});

it('update() with a rich field and the CURRENT expected_updated_at succeeds', function () {
    $admin = crfAdmin();
    $article = crfArticle(['image_credit' => 'Credit original']);
    $current = $article->fresh()->updated_at->toIso8601String();

    $response = $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'image_credit' => 'Credit mis a jour avec le bon jeton',
        'expected_updated_at' => $current,
    ]);

    $response->assertOk();
    expect($article->fresh()->image_credit)->toBe('Credit mis a jour avec le bon jeton');
});

// ── show() expose les nouveaux champs et les listes d'options (design doc section 2.8) ──

it('show() exposes nature_original, niveau_preuve and their option lists from the model constants', function () {
    $admin = crfAdmin();
    $article = crfArticle([
        'nature_original' => array_key_first(NewsArticle::NATURE_ORIGINAL_VALUES),
        'niveau_preuve' => array_key_first(NewsArticle::NIVEAU_PREUVE_VALUES),
    ]);

    $this->actingAs($admin)->getJson(route('admin.news.composition.show', $article))
        ->assertOk()
        ->assertJson([
            'nature_original' => array_key_first(NewsArticle::NATURE_ORIGINAL_VALUES),
            'niveau_preuve' => array_key_first(NewsArticle::NIVEAU_PREUVE_VALUES),
            'nature_original_options' => NewsArticle::NATURE_ORIGINAL_VALUES,
            'niveau_preuve_options' => NewsArticle::NIVEAU_PREUVE_VALUES,
        ]);
});

// ── storeProofPair() apres la delegation a CompositionPayloadNormalizer (design doc 2.2) ──

it('storeProofPair() still accepts a valid fact pair after the refactor to the shared service', function () {
    $admin = crfAdmin();
    $article = crfArticle(['internal_source_text' => 'Le comite a vote un budget de 5 millions.']);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.proof-pairs.store', $article), [
        'statement' => 'Le comite a approuve un budget de 5 millions.',
        'excerpt' => 'un budget de 5 millions',
        'type' => 'fact',
    ]);

    $response->assertOk()->assertJson(['success' => true]);
    expect($article->fresh()->editorial_proof_pairs)->toHaveCount(1);
});

it('storeProofPair() still refuses a fact pair whose excerpt is not a substring, after the refactor', function () {
    $admin = crfAdmin();
    $article = crfArticle(['internal_source_text' => 'Le comite a vote un budget de 5 millions.']);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.proof-pairs.store', $article), [
        'statement' => 'Le comite a approuve un budget de 9 millions.',
        'excerpt' => 'un budget de 9 millions',
        'type' => 'fact',
    ]);

    $response->assertStatus(422);
    expect($article->fresh()->editorial_proof_pairs ?? [])->toBeEmpty();
});

it('storeProofPair() a primary_fact pair still requires a valid source_url, after the refactor', function () {
    $admin = crfAdmin();
    $article = crfArticle();

    $this->actingAs($admin)->postJson(route('admin.news.composition.proof-pairs.store', $article), [
        'statement' => 'Une citation exacte de l original.',
        'excerpt' => 'citation exacte',
        'type' => 'primary_fact',
    ])->assertStatus(422);

    $withUrl = $this->actingAs($admin)->postJson(route('admin.news.composition.proof-pairs.store', $article), [
        'statement' => 'Une citation exacte de l original.',
        'excerpt' => 'citation exacte',
        'type' => 'primary_fact',
        'source_url' => 'https://exemple.com/original',
    ]);
    $withUrl->assertOk();
    $pairs = $article->fresh()->editorial_proof_pairs;
    expect($pairs)->toHaveCount(1)
        ->and($pairs[0]['source_url'])->toBe('https://exemple.com/original');
});

it('storeProofPair() and destroyProofPair() report a fresh updated_at, so a following rich update does not self-inflict a 409', function () {
    $admin = crfAdmin();
    $article = crfArticle(['internal_source_text' => 'Le rapport confirme une baisse de 3 pourcent.']);

    $storeResponse = $this->actingAs($admin)->postJson(route('admin.news.composition.proof-pairs.store', $article), [
        'statement' => 'Une baisse de 3 pourcent est confirmee.',
        'excerpt' => 'une baisse de 3 pourcent',
        'type' => 'fact',
    ]);
    $storeResponse->assertOk();
    $afterStore = $storeResponse->json('updated_at');
    expect($afterStore)->not->toBeNull();

    // Le jeton renvoye par storeProofPair() (pas celui capture a l'ouverture de l'ecran) doit
    // reussir un update() riche immediatement apres, sans 409 auto-inflige.
    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'image_credit' => 'Credit ajoute juste apres une preuve',
        'expected_updated_at' => $afterStore,
    ])->assertOk();

    // destroyProofPair() doit reporter exactement le meme mecanisme : son jeton, lui aussi,
    // doit reussir le prochain update() riche.
    $pairId = $article->fresh()->editorial_proof_pairs[0]['id'];
    $destroyResponse = $this->actingAs($admin)->deleteJson(
        route('admin.news.composition.proof-pairs.destroy', ['article' => $article, 'pair' => $pairId])
    );
    $destroyResponse->assertOk();
    $afterDestroy = $destroyResponse->json('updated_at');
    expect($afterDestroy)->not->toBeNull();

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'image_credit' => 'Credit ajoute juste apres un retrait de preuve',
        'expected_updated_at' => $afterDestroy,
    ])->assertOk();
    expect($article->fresh()->image_credit)->toBe('Credit ajoute juste apres un retrait de preuve');
});

// ── rendu (Lot 4a, design doc section 2.7) - le panneau toujours visible compile et s'affiche ──

it('the composition screen renders the always-visible panel fields, outside the collapsed details', function () {
    $admin = crfAdmin();

    $response = $this->actingAs($admin)->get(route('admin.news.composition.index'));
    $response->assertOk();
    $html = $response->getContent();

    expect($html)
        ->toContain('id="nc-title-publie"')
        ->toContain('x-model="formTitle"')
        ->toContain('id="nc-image-credit"')
        ->toContain('id="nc-nature-original"')
        ->toContain('id="nc-niveau-preuve"')
        ->toContain('primary_fact')
        ->toContain('id="nc-pair-source-url"')
        ->toContain('Titre SEO (balise');
});
