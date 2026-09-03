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

// ── slug renvoyé dans la réponse JSON de update() (défaut mesuré en QC visuelle, 2026-09-03) ──
// Sans ce renvoi, l'écran gardait l'ancien slug après un changement de titre et son PROCHAIN
// enregistrement visait une URL périmée (404 « Ressource introuvable »), modifications perdues
// dès le second enregistrement consécutif sur un brouillon fraîchement renommé.

it('update() JSON response echoes the fresh slug matching the new title, on a DRAFT article', function () {
    $admin = crfAdmin();
    $article = crfArticle(['is_published' => false, 'title' => 'Ancien titre echo', 'slug' => 'ancien-slug-echo-crf']);

    $response = $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'title' => 'Titre frais pour verifier l echo du slug',
        'expected_updated_at' => crfToken($article),
    ]);

    $response->assertOk();
    // Le slug renvoyé doit être celui EFFECTIVEMENT écrit en base, pas une reconstruction locale
    // recalculée côté client à partir du titre (l'unique source de vérité reste le serveur).
    expect($response->json('slug'))
        ->toBe($article->fresh()->slug)
        ->toContain('titre-frais-pour-verifier-l-echo-du-slug');
});

it('update() JSON response echoes the UNCHANGED slug on an ALREADY PUBLISHED article', function () {
    $admin = crfAdmin();
    $article = crfArticle(['is_published' => true, 'title' => 'Titre publie echo original', 'slug' => 'slug-fige-echo-crf']);

    $response = $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'title' => 'Titre publie echo corrige',
        'expected_updated_at' => crfToken($article),
    ]);

    $response->assertOk();
    // La garde `if (! $article->is_published)` protège les URL publiques : le slug ne doit JAMAIS
    // bouger ici, ni en base ni dans la réponse échoée - c'est elle qui rend ce test 2 pertinent en
    // plus du test 1 : le renvoi du slug ne doit pas devenir un second chemin qui le ferait bouger.
    expect($response->json('slug'))
        ->toBe('slug-fige-echo-crf')
        ->and($article->fresh()->slug)->toBe('slug-fige-echo-crf');
});

it('two consecutive update() calls on a draft both succeed when the second targets the slug from the first response', function () {
    $admin = crfAdmin();
    $article = crfArticle(['is_published' => false, 'title' => 'Titre depart chainage', 'slug' => 'slug-depart-chainage-crf']);

    $first = $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'title' => 'Premier changement de titre du chainage',
        'expected_updated_at' => crfToken($article),
    ]);
    $first->assertOk();
    $slugAfterFirst = $first->json('slug');
    expect($slugAfterFirst)->not->toBeNull()->not->toBe('slug-depart-chainage-crf');

    // Reproduction exacte du défaut mesuré : l'écran enchaîne sur le slug RENVOYÉ par le premier
    // appel, jamais sur celui capturé à l'ouverture de l'écran - sans le correctif, cette URL
    // pointait vers l'ANCIEN slug et ne résolvait plus (404 « Ressource introuvable »).
    $second = $this->actingAs($admin)->putJson(
        route('admin.news.composition.update', ['article' => $slugAfterFirst]),
        [
            'title' => 'Second changement de titre, enchaine sur le slug frais',
            'expected_updated_at' => crfToken($article),
        ]
    );

    $second->assertOk();
    $fresh = $article->fresh();
    expect($fresh->title)->toBe('Second changement de titre, enchaine sur le slug frais')
        ->and($fresh->slug)->toBe($second->json('slug'));
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

    // destroyProofPair() doit reporter exactement le même mécanisme : son jeton, lui aussi,
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

// ═══════════════════════════════════════════════════════════════════════════════════
// LOT 4B (design doc "extension de l'écran de composition des actualités", 2026-09-03,
// section 2.5 et section 11) - composed_summary, primary_sources, related_tool_slugs.
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Même convention que nacTool() de NewsApplyCommandTest.php (préfixée crf, pas nac : Pest
 * charge tous les fichiers de test dans un seul processus, un nom nu entrerait en collision).
 * Tableau associatif (PAS json_encode) pour que Spatie appelle setTranslations() correctement -
 * même piège déjà documenté par nacTool().
 */
function crfTool(string $slug): \Modules\Directory\Models\Tool
{
    $name = 'Outil crf '.$slug;

    return \Modules\Directory\Models\Tool::withoutEvents(fn () => \Modules\Directory\Models\Tool::create([
        'name' => ['fr_CA' => $name, 'en' => $name],
        'slug' => ['fr_CA' => $slug, 'en' => $slug],
        'status' => 'published',
        'pricing' => 'free',
    ]));
}

// ── composed_summary (design doc section 2.5) - fusion sous-clé par sous-clé ─────────

it('update() writes a fresh composed_summary and flags the fiche as composed', function () {
    $admin = crfAdmin();
    $article = crfArticle(['structured_summary' => null]);

    $response = $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'composed_summary' => [
            'hook' => 'Une accroche composee par un humain.',
            'key_points' => ['Premier point.', 'Deuxieme point.'],
            'why_important' => 'Parce que ca compte.',
        ],
        'expected_updated_at' => crfToken($article),
    ]);

    $response->assertOk();
    $fresh = $article->fresh();
    expect($fresh->structured_summary['hook'])->toBe('Une accroche composee par un humain.')
        ->and($fresh->structured_summary['key_points'])->toBe(['Premier point.', 'Deuxieme point.'])
        ->and($fresh->structured_summary['why_important'])->toBe('Parce que ca compte.')
        ->and($fresh->structured_summary['composed'])->toBeTrue()
        ->and($fresh->hasComposedSummary())->toBeTrue();
});

it('update() FUSES composed_summary sub-key by sub-key, preserving what the payload omits', function () {
    $admin = crfAdmin();
    $article = crfArticle(['structured_summary' => [
        'composed' => true,
        'hook' => 'Accroche existante.',
        'why_important' => 'Raison existante.',
    ]]);

    $response = $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'composed_summary' => ['key_number' => '5 millions de dollars'],
        'expected_updated_at' => crfToken($article),
    ]);

    $response->assertOk();
    $fresh = $article->fresh();
    expect($fresh->structured_summary['hook'])->toBe('Accroche existante.')
        ->and($fresh->structured_summary['why_important'])->toBe('Raison existante.')
        ->and($fresh->structured_summary['key_number'])->toBe('5 millions de dollars');
});

it('update() clears a single composed_summary sub-key sent explicitly null, without touching the others', function () {
    $admin = crfAdmin();
    $article = crfArticle(['structured_summary' => [
        'composed' => true,
        'hook' => 'Accroche a effacer.',
        'why_important' => 'Raison qui doit survivre.',
    ]]);

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'composed_summary' => ['hook' => null],
        'expected_updated_at' => crfToken($article),
    ])->assertOk();

    $fresh = $article->fresh();
    expect($fresh->structured_summary)->not->toHaveKey('hook')
        ->and($fresh->structured_summary['why_important'])->toBe('Raison qui doit survivre.');
});

it('update() rejects an unknown composed_summary sub-key with 422, writes nothing', function () {
    $admin = crfAdmin();
    $article = crfArticle(['structured_summary' => null]);

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'composed_summary' => ['cle_qui_nexiste_pas' => 'valeur'],
        'expected_updated_at' => crfToken($article),
    ])->assertStatus(422);

    expect($article->fresh()->structured_summary)->toBeNull();
});

it('update() treats a top-level composed_summary null as a no-op - never wipes an existing machine summary', function () {
    $admin = crfAdmin();
    // Résumé MACHINE (jamais composé) - mêmes noms de sous-clés que composed_summary :
    // AiSummaryService écrit hook/key_points/why_important/angle_qc_ca (piège identifié en
    // revue de ce lot - voir le commentaire de composed_summary_active dans le contrôleur).
    $article = crfArticle(['structured_summary' => [
        'hook' => 'Accroche machine, jamais composee.',
        'key_points' => ['Fait machine 1.', 'Fait machine 2.'],
        'why_important' => 'Importance calculee par la machine.',
    ]]);

    $response = $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'composed_summary' => null,
        'image_credit' => 'Credit ajoute en meme temps',
        'expected_updated_at' => crfToken($article),
    ]);

    $response->assertOk();
    $fresh = $article->fresh();
    expect($fresh->structured_summary['hook'])->toBe('Accroche machine, jamais composee.')
        ->and($fresh->structured_summary['key_points'])->toBe(['Fait machine 1.', 'Fait machine 2.'])
        ->and($fresh->hasComposedSummary())->toBeFalse()
        ->and($fresh->image_credit)->toBe('Credit ajoute en meme temps');
});

it('update() with composed_summary entirely absent from the payload never touches an existing machine summary', function () {
    $admin = crfAdmin();
    $article = crfArticle(['structured_summary' => ['hook' => 'Accroche machine intacte.']]);

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'image_credit' => 'Credit seul, sans composed_summary du tout',
        'expected_updated_at' => crfToken($article),
    ])->assertOk();

    expect($article->fresh()->structured_summary['hook'])->toBe('Accroche machine intacte.');
});

it('update() with composed_summary and a stale expected_updated_at is refused with 409, writes nothing', function () {
    $admin = crfAdmin();
    $article = crfArticle(['structured_summary' => null]);

    $response = $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'composed_summary' => ['hook' => 'Ne doit jamais s ecrire'],
        'expected_updated_at' => '2000-01-01T00:00:00-05:00',
    ]);

    $response->assertStatus(409);
    expect($article->fresh()->structured_summary)->toBeNull();
});

// ── primary_sources (design doc section 2.5) - remplacement complet, plafond 10 ──────

it('update() replaces primary_sources completely, not an accumulation', function () {
    $admin = crfAdmin();
    $article = crfArticle(['primary_sources' => [
        ['label' => 'Ancienne source', 'url' => 'https://exemple.com/ancienne', 'note' => null],
    ]]);

    $response = $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'primary_sources' => [
            ['label' => 'Nouvelle source', 'url' => 'https://exemple.com/nouvelle'],
        ],
        'expected_updated_at' => crfToken($article),
    ]);

    $response->assertOk();
    $sources = $article->fresh()->primary_sources;
    expect($sources)->toHaveCount(1)
        ->and($sources[0]['label'])->toBe('Nouvelle source')
        ->and($sources[0]['url'])->toBe('https://exemple.com/nouvelle');
});

it('update() accepts an empty primary_sources array to clear all sources', function () {
    $admin = crfAdmin();
    $article = crfArticle(['primary_sources' => [
        ['label' => 'Source a vider', 'url' => 'https://exemple.com/a-vider', 'note' => null],
    ]]);

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'primary_sources' => [],
        'expected_updated_at' => crfToken($article),
    ])->assertOk();

    expect($article->fresh()->primary_sources)->toBe([]);
});

it('update() rejects a primary_sources entry without a valid url with 422, writes nothing', function () {
    $admin = crfAdmin();
    $article = crfArticle(['primary_sources' => []]);

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'primary_sources' => [
            ['label' => 'Source sans url valide', 'url' => 'pas-une-url'],
        ],
        'expected_updated_at' => crfToken($article),
    ])->assertStatus(422);

    expect($article->fresh()->primary_sources)->toBe([]);
});

it('update() rejects more than 10 primary_sources with 422, writes nothing', function () {
    $admin = crfAdmin();
    $article = crfArticle(['primary_sources' => []]);

    $tropDeSources = array_map(
        fn (int $i) => ['label' => "Source {$i}", 'url' => "https://exemple.com/source-{$i}"],
        range(1, 11)
    );

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'primary_sources' => $tropDeSources,
        'expected_updated_at' => crfToken($article),
    ])->assertStatus(422);

    expect($article->fresh()->primary_sources)->toBe([]);
});

it('update() with primary_sources and a stale expected_updated_at is refused with 409, writes nothing', function () {
    $admin = crfAdmin();
    $article = crfArticle(['primary_sources' => []]);

    $response = $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'primary_sources' => [['label' => 'Ne doit jamais s ecrire', 'url' => 'https://exemple.com/x']],
        'expected_updated_at' => '2000-01-01T00:00:00-05:00',
    ]);

    $response->assertStatus(409);
    expect($article->fresh()->primary_sources)->toBe([]);
});

// ── related-tools.store / related-tools.destroy (design doc section 2.5) - additif/soustractif, jamais un remplacement ──

it('storeRelatedTool() attaches a published tool by slug', function () {
    $admin = crfAdmin();
    $article = crfArticle();
    $tool = crfTool('outil-crf-un');

    $response = $this->actingAs($admin)->postJson(
        route('admin.news.composition.related-tools.store', $article),
        ['tool_slug' => 'outil-crf-un']
    );

    $response->assertOk()->assertJson(['success' => true]);
    expect($article->fresh()->tools->pluck('id')->all())->toBe([$tool->id]);
});

it('storeRelatedTool() with an unknown slug refuses with 422, attaches nothing', function () {
    $admin = crfAdmin();
    $article = crfArticle();

    $this->actingAs($admin)->postJson(
        route('admin.news.composition.related-tools.store', $article),
        ['tool_slug' => 'slug-qui-nexiste-pas']
    )->assertStatus(422);

    expect($article->fresh()->tools)->toHaveCount(0);
});

it('storeRelatedTool() is purely additive - adding a second tool never detaches the first', function () {
    $admin = crfAdmin();
    $article = crfArticle();
    $premier = crfTool('outil-crf-premier');
    $second = crfTool('outil-crf-second');

    $this->actingAs($admin)->postJson(route('admin.news.composition.related-tools.store', $article), ['tool_slug' => 'outil-crf-premier'])->assertOk();
    $this->actingAs($admin)->postJson(route('admin.news.composition.related-tools.store', $article), ['tool_slug' => 'outil-crf-second'])->assertOk();

    $ids = $article->fresh()->tools->pluck('id')->sort()->values()->all();
    $expected = collect([$premier->id, $second->id])->sort()->values()->all();
    expect($ids)->toBe($expected);
});

it('destroyRelatedTool() detaches only the targeted tool, leaves the others intact', function () {
    $admin = crfAdmin();
    $article = crfArticle();
    $aGarder = crfTool('outil-crf-a-garder');
    $aRetirer = crfTool('outil-crf-a-retirer');
    $article->tools()->attach([$aGarder->id, $aRetirer->id], ['source' => 'manual']);

    $response = $this->actingAs($admin)->deleteJson(
        route('admin.news.composition.related-tools.destroy', ['article' => $article, 'slug' => 'outil-crf-a-retirer'])
    );

    $response->assertOk();
    expect($article->fresh()->tools->pluck('id')->all())->toBe([$aGarder->id]);
});

it('destroyRelatedTool() on a valid but not-attached slug is idempotent - 200, nothing breaks', function () {
    $admin = crfAdmin();
    $article = crfArticle();
    crfTool('outil-crf-jamais-lie');

    $response = $this->actingAs($admin)->deleteJson(
        route('admin.news.composition.related-tools.destroy', ['article' => $article, 'slug' => 'outil-crf-jamais-lie'])
    );

    $response->assertOk()->assertJson(['success' => true]);
});

it('destroyRelatedTool() on a slug unknown to the directory refuses with 422', function () {
    $admin = crfAdmin();
    $article = crfArticle();

    $this->actingAs($admin)->deleteJson(
        route('admin.news.composition.related-tools.destroy', ['article' => $article, 'slug' => 'slug-totalement-inconnu'])
    )->assertStatus(422);
});

it('storeRelatedTool() and destroyRelatedTool() never require an optimistic-lock token - narrow endpoints, not update()', function () {
    $admin = crfAdmin();
    $article = crfArticle();
    crfTool('outil-crf-sans-verrou');

    // Aucun expected_updated_at fourni, et il n'y a même pas de clé riche dans un update() ici :
    // ces deux endpoints étroits ne portent pas le verrou optimiste (design doc section 2.6).
    $this->actingAs($admin)->postJson(
        route('admin.news.composition.related-tools.store', $article),
        ['tool_slug' => 'outil-crf-sans-verrou']
    )->assertOk();

    $this->actingAs($admin)->deleteJson(
        route('admin.news.composition.related-tools.destroy', ['article' => $article, 'slug' => 'outil-crf-sans-verrou'])
    )->assertOk();
});

// ── show() expose composed_summary, composed_summary_active et related_tools (design doc 2.8) ──

it('show() exposes composed_summary mirroring structured_summary, and composed_summary_active true when already composed', function () {
    $admin = crfAdmin();
    $article = crfArticle(['structured_summary' => [
        'composed' => true,
        'hook' => 'Accroche exposee par show().',
    ]]);

    $this->actingAs($admin)->getJson(route('admin.news.composition.show', $article))
        ->assertOk()
        ->assertJson([
            'composed_summary' => ['composed' => true, 'hook' => 'Accroche exposee par show().'],
            'composed_summary_active' => true,
        ]);
});

it('show() reports composed_summary_active as false for a machine (non-composed) summary', function () {
    $admin = crfAdmin();
    $article = crfArticle(['structured_summary' => ['hook' => 'Accroche machine.']]);

    $this->actingAs($admin)->getJson(route('admin.news.composition.show', $article))
        ->assertOk()
        ->assertJson(['composed_summary_active' => false]);
});

it('show() exposes the tools already linked to the fiche, by slug and label', function () {
    $admin = crfAdmin();
    $article = crfArticle();
    crfTool('outil-crf-affiche');
    $article->tools()->attach(\Modules\Directory\Models\Tool::where('slug->fr_CA', 'outil-crf-affiche')->first()->id, ['source' => 'manual']);

    $response = $this->actingAs($admin)->getJson(route('admin.news.composition.show', $article));
    $response->assertOk();
    $related = $response->json('related_tools');
    expect($related)->toHaveCount(1)
        ->and($related[0]['slug'])->toBe('outil-crf-affiche');
});

// ── rendu (Lot 4b, design doc section 2.7) - résumé structuré, sources primaires, outils liés ──

it('the composition screen renders the Lot 4b panel fields - composed summary, primary sources, related tools', function () {
    $admin = crfAdmin();

    $response = $this->actingAs($admin)->get(route('admin.news.composition.index'));
    $response->assertOk();
    $html = $response->getContent();

    expect($html)
        ->toContain('id="nc-composed-summary"')
        ->toContain('id="nc-summary-hook"')
        ->toContain('x-model="formHook"')
        ->toContain('id="nc-primary-sources"')
        ->toContain('x-model="source.label"')
        ->toContain('id="nc-related-tool-select"')
        ->toContain('initRelatedToolPicker');
});
