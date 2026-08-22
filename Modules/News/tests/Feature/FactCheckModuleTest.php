<?php

declare(strict_types=1);

/**
 * Tests du module « vérification » (fact-check) - 2026-08-21, demande fondateur : badge qui
 * démonte une affirmation circulant ailleurs (citation inexacte, attribution erronée,
 * présentation trompeuse, contexte manquant), balisage Schema.org ClaimReview, et page publique
 * dédiée /verifications.
 *
 * Couvre les quatre points d'entrée du module :
 *   A. la porte d'écriture news:apply --payload (clé fact_check, Modules\News\Console\
 *      NewsApplyCommand) ;
 *   B. le modèle (Modules\News\Models\NewsArticle : FACT_CHECK_VERDICTS, hasFactCheck(),
 *      factCheckVerdict(), scopeFactChecked()) ;
 *   C. le rendu public du badge (Modules\News\resources\views\components\
 *      fact-check-badge.blade.php, inclus dans public\show.blade.php) ;
 *   D. le balisage JSON-LD ClaimReview (Modules\SEO\Services\JsonLdService::claimReview()) ;
 *   E. la route publique dédiée /verifications (Modules\News\Http\Controllers\
 *      PublicNewsController::verifications()).
 *
 * Fichier dédié, distinct des fichiers de tests existants sur news:apply (NewsApplyCommandTest.php,
 * Actu2PayloadFieldsTest.php) et sur le rendu public (NewsShowLayoutTest.php) - helpers locaux
 * préfixés `fcm` (FactCheckModule), autonomes.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés fcm pour éviter tout conflit inter-fichiers) ──────────────

function fcmSource(): NewsSource
{
    // URL toujours unique : plusieurs tests créent 2+ fiches (donc 2+ sources) dans le même
    // test, et news_sources.url porte une contrainte d'unicité.
    return NewsSource::create([
        'name' => 'Source vérification',
        'url' => 'https://fcm-source.exemple.com/rss-'.uniqid(),
        'language' => 'fr',
        'active' => true,
    ]);
}

function fcmArticle(array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();
    $source = fcmSource();

    return NewsArticle::create(array_merge([
        'news_source_id' => $source->id,
        'title' => "Article vérification {$i}",
        'guid' => "guid-fcm-{$suffix}",
        'url' => "https://exemple.com/fcm-{$suffix}",
        'description' => '',
        'summary' => "Résumé initial {$i}",
        'slug' => "article-fcm-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));
}

function fcmPublishedArticle(string $slug, array $overrides = []): NewsArticle
{
    $source = fcmSource();

    return NewsArticle::create(array_merge([
        'news_source_id' => $source->id,
        'title' => 'Article publié '.$slug,
        'guid' => 'guid-fcm-pub-'.$slug,
        'url' => 'https://exemple.com/fcm-pub-'.$slug,
        'description' => '',
        'summary' => 'Résumé publié pour '.$slug.'.',
        'slug' => $slug,
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

function fcmPayloadFile(array $data): string
{
    $path = sys_get_temp_dir().'/fcm-payload-'.uniqid().'.json';
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $path;
}

function fcmFreshMeta(NewsArticle $article): array
{
    $article = $article->fresh();

    return [
        'expected_source_hash' => $article->source_content_hash,
        'expected_updated_at' => $article->updated_at?->toIso8601String(),
    ];
}

function fcmAdmin(): \App\Models\User
{
    // Même gabarit que ncbAdmin()/a2cAdmin() (NewsCompositionBuilderTest.php,
    // Actu2CompositionScreenTest.php) : super_admin satisfait EnsureIsAdmin
    // (permission view_admin_panel), et l'absence de 2FA activée laisse passer
    // EnsureTwoFactorAuthenticated sans configuration supplémentaire.
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

// ── A. Porte d'écriture news:apply --payload (clé fact_check) ──────────────────────────

it('applique un fact_check valide (verdict, affirmation, source) sur les trois colonnes', function () {
    $article = fcmArticle();
    $payload = fcmPayloadFile(array_merge(fcmFreshMeta($article), [
        'fact_check' => [
            'verdict' => 'citation_inexacte',
            'claim' => 'Le ministre aurait déclaré vouloir abolir la mesure dès septembre.',
            'source' => 'https://x.com/exemple/status/1234567890',
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $fresh = $article->fresh();
    expect($fresh->fact_check_verdict)->toBe('citation_inexacte')
        ->and($fresh->fact_check_claim)->toBe('Le ministre aurait déclaré vouloir abolir la mesure dès septembre.')
        ->and($fresh->fact_check_source)->toBe('https://x.com/exemple/status/1234567890');
});

it('refuse un verdict absent du vocabulaire, sans rien persister', function () {
    $article = fcmArticle();
    $payload = fcmPayloadFile(array_merge(fcmFreshMeta($article), [
        'fact_check' => [
            'verdict' => 'verdict-invente',
            'claim' => 'Affirmation quelconque.',
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    $fresh = $article->fresh();
    expect($fresh->fact_check_verdict)->toBeNull()
        ->and($fresh->fact_check_claim)->toBeNull()
        ->and($fresh->fact_check_source)->toBeNull();
});

it("refuse une affirmation examinée vide", function () {
    $article = fcmArticle();
    $payload = fcmPayloadFile(array_merge(fcmFreshMeta($article), [
        'fact_check' => [
            'verdict' => 'contexte_manquant',
            'claim' => '',
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->fact_check_verdict)->toBeNull();
});

it('refuse une affirmation examinée de plus de 300 caractères', function () {
    $article = fcmArticle();
    $payload = fcmPayloadFile(array_merge(fcmFreshMeta($article), [
        'fact_check' => [
            'verdict' => 'contexte_manquant',
            'claim' => str_repeat('a', 301),
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->fact_check_verdict)->toBeNull();
});

it('refuse une source qui n\'est pas une URL valide', function () {
    $article = fcmArticle();
    $payload = fcmPayloadFile(array_merge(fcmFreshMeta($article), [
        'fact_check' => [
            'verdict' => 'presentation_trompeuse',
            'claim' => 'Affirmation quelconque.',
            'source' => 'ceci-n-est-pas-une-url',
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    $fresh = $article->fresh();
    expect($fresh->fact_check_verdict)->toBeNull()
        ->and($fresh->fact_check_source)->toBeNull();
});

it('fact_check à null efface les trois colonnes d\'une fiche qui portait un verdict', function () {
    $article = fcmArticle([
        'fact_check_verdict' => 'attribution_erronee',
        'fact_check_claim' => 'Affirmation déjà vérifiée.',
        'fact_check_source' => 'https://x.com/exemple/status/999',
    ]);
    $payload = fcmPayloadFile(array_merge(fcmFreshMeta($article), ['fact_check' => null]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $fresh = $article->fresh();
    expect($fresh->fact_check_verdict)->toBeNull()
        ->and($fresh->fact_check_claim)->toBeNull()
        ->and($fresh->fact_check_source)->toBeNull();
});

// ── A2. Panier séparé fact_check / panier de contenu (défaut réel corrigé 2026-08-21) ──
// Les trois colonnes fact_check_* voyagent dans leur propre panier ($factCheckUpdates),
// jamais dans celui du contenu rédactionnel ($updates) : ce dernier efface toujours
// structured_summary. Poser un verdict seul ne doit donc jamais toucher au résumé composé,
// alors qu'un payload de contenu doit continuer de l'effacer comme avant.

it('un payload ne portant que fact_check ne détruit pas le résumé composé de la fiche', function () {
    $article = fcmArticle([
        'structured_summary' => ['composed' => true, 'hook' => 'MARQUEUR-RESUME-COMPOSE-A-PRESERVER'],
    ]);
    $payload = fcmPayloadFile(array_merge(fcmFreshMeta($article), [
        'fact_check' => [
            'verdict' => 'citation_inexacte',
            'claim' => 'Affirmation à vérifier, posée après coup sur une fiche déjà composée.',
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $fresh = $article->fresh();
    expect($fresh->structured_summary)->not->toBeNull()
        ->and($fresh->structured_summary['composed'])->toBeTrue()
        ->and($fresh->structured_summary['hook'])->toBe('MARQUEUR-RESUME-COMPOSE-A-PRESERVER')
        ->and($fresh->fact_check_verdict)->toBe('citation_inexacte');
});

it('un payload de contenu efface toujours le résumé machine', function () {
    $article = fcmArticle([
        'structured_summary' => ['hook' => 'MARQUEUR-RESUME-MACHINE-A-EFFACER'],
    ]);
    $payload = fcmPayloadFile(array_merge(fcmFreshMeta($article), [
        'summary' => 'Nouveau résumé court appliqué par la porte.',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->structured_summary)->toBeNull();
});

it('le mode --enrich pose un verdict sur une fiche déjà publiée', function () {
    $article = fcmPublishedArticle('enrich-verdict-apres-coup');
    $ancienSlug = $article->slug;
    $payload = fcmPayloadFile(array_merge(fcmFreshMeta($article), [
        'fact_check' => [
            'verdict' => 'attribution_erronee',
            'claim' => 'Verdict posé après coup sur une fiche déjà publiée.',
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload, '--enrich' => true])
        ->assertSuccessful();

    $fresh = $article->fresh();
    expect($fresh->fact_check_verdict)->toBe('attribution_erronee')
        ->and($fresh->is_published)->toBeTrue()
        ->and($fresh->slug)->toBe($ancienSlug);
});

// ── A3. Source : schéma http(s) strict, absence ≠ effacement (relecture adversariale du
// diff avant déploiement, 2026-08-21) ───────────────────────────────────────────────────

it("refuse une source dont le schéma n'est pas http ou https", function () {
    $article = fcmArticle();
    $payload = fcmPayloadFile(array_merge(fcmFreshMeta($article), [
        'fact_check' => [
            'verdict' => 'citation_inexacte',
            'claim' => 'Affirmation quelconque.',
            'source' => 'javascript://commentaire%0aalert(1)',
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    $fresh = $article->fresh();
    expect($fresh->fact_check_verdict)->toBeNull()
        ->and($fresh->fact_check_claim)->toBeNull()
        ->and($fresh->fact_check_source)->toBeNull();
});

it('un verdict reposé sans clé source laisse la source déjà enregistrée intacte', function () {
    $article = fcmArticle([
        'fact_check_verdict' => 'contexte_manquant',
        'fact_check_claim' => 'Ancienne affirmation.',
        'fact_check_source' => 'https://x.com/exemple/status/555',
    ]);
    $payload = fcmPayloadFile(array_merge(fcmFreshMeta($article), [
        'fact_check' => [
            'verdict' => 'attribution_erronee',
            'claim' => 'Nouvelle affirmation, source non repréciée.',
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $fresh = $article->fresh();
    expect($fresh->fact_check_verdict)->toBe('attribution_erronee')
        ->and($fresh->fact_check_claim)->toBe('Nouvelle affirmation, source non repréciée.')
        ->and($fresh->fact_check_source)->toBe('https://x.com/exemple/status/555');
});

it('une source explicitement nulle efface la source enregistrée', function () {
    $article = fcmArticle([
        'fact_check_verdict' => 'contexte_manquant',
        'fact_check_claim' => 'Ancienne affirmation.',
        'fact_check_source' => 'https://x.com/exemple/status/555',
    ]);
    $payload = fcmPayloadFile(array_merge(fcmFreshMeta($article), [
        'fact_check' => [
            'verdict' => 'attribution_erronee',
            'claim' => 'Nouvelle affirmation, source explicitement retirée.',
            'source' => null,
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $fresh = $article->fresh();
    expect($fresh->fact_check_verdict)->toBe('attribution_erronee')
        ->and($fresh->fact_check_source)->toBeNull();
});

it("le badge public n'affiche aucun lien quand fact_check_source ne porte pas un schéma http(s) (défense en profondeur)", function () {
    $article = fcmPublishedArticle('badge-source-schema-non-http', [
        'fact_check_verdict' => 'citation_inexacte',
        'fact_check_claim' => 'Affirmation quelconque.',
        // Donnée écrite hors de la porte (legacy ou autre chemin) : le composant filtre lui
        // aussi le schéma avant de le poser dans un href, en défense en profondeur.
        'fact_check_source' => 'javascript://commentaire%0aalert(1)',
    ]);

    $response = $this->get(route('news.show', $article));

    // Note : 'nw-factcheck__source' n'est pas un bon marqueur d'absence à lui seul - ce nom de
    // classe vit AUSSI dans le <style> toujours émis dès qu'un verdict existe (ligne 78 du
    // composant), indépendamment de la présence d'une source. Le marqueur fiable est la balise
    // OUVRANTE réelle du bloc conditionnel (@if($nwFcSource) du composant), jamais le nom de
    // classe seul.
    $response->assertOk()
        ->assertSee('nw-factcheck', false)
        ->assertDontSee('<span class="nw-factcheck__source">', false)
        ->assertDontSee('javascript:', false);
});

// ── A4. Sous-clés en liste blanche + borne de longueur de la source (seconde relecture
// adversariale, Codex, 2026-08-21) ──────────────────────────────────────────────────────

it('refuse une sous-clé inconnue dans fact_check', function () {
    $article = fcmArticle();
    $payload = fcmPayloadFile(array_merge(fcmFreshMeta($article), [
        'fact_check' => [
            'verdict' => 'citation_inexacte',
            'claim' => 'Affirmation quelconque.',
            // Typo volontaire ('souce' au lieu de 'source') : avant ce garde-fou, la clé était
            // ignorée en silence et l'agent croyait avoir posé une source inexistante.
            'souce' => 'https://x.com/exemple/status/1',
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->expectsOutputToContain('souce')
        ->assertFailed();

    $fresh = $article->fresh();
    expect($fresh->fact_check_verdict)->toBeNull()
        ->and($fresh->fact_check_claim)->toBeNull()
        ->and($fresh->fact_check_source)->toBeNull();
});

it('refuse une source de plus de 2048 caractères, et n\'applique pas non plus le reste du payload', function () {
    $article = fcmArticle();
    $urlTropLongue = 'https://exemple.com/'.str_repeat('a', 2048); // > 2048 caractères au total
    $payload = fcmPayloadFile(array_merge(fcmFreshMeta($article), [
        // Contenu du MÊME payload : doit rester non appliqué, c'est tout l'intérêt du
        // garde-fou (refuser AVANT d'écrire quoi que ce soit, jamais une application partielle).
        'summary' => 'Résumé qui ne doit jamais être appliqué.',
        'fact_check' => [
            'verdict' => 'citation_inexacte',
            'claim' => 'Affirmation quelconque.',
            'source' => $urlTropLongue,
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    $fresh = $article->fresh();
    expect($fresh->fact_check_verdict)->toBeNull()
        ->and($fresh->fact_check_source)->toBeNull()
        ->and($fresh->summary)->not->toBe('Résumé qui ne doit jamais être appliqué.');
});

// ── B. Modèle : hasFactCheck(), factCheckVerdict(), scopeFactChecked() ─────────────────

it('hasFactCheck est faux par défaut, sans verdict posé', function () {
    $article = fcmArticle();

    expect($article->hasFactCheck())->toBeFalse();
});

it('hasFactCheck est vrai dès qu\'un verdict connu est posé', function () {
    $article = fcmArticle(['fact_check_verdict' => 'contexte_manquant']);

    expect($article->hasFactCheck())->toBeTrue();
});

it('hasFactCheck est faux pour un verdict retiré du vocabulaire après coup', function () {
    $article = fcmArticle();
    $article->fact_check_verdict = 'verdict-retire-du-vocabulaire';

    expect($article->hasFactCheck())->toBeFalse();
});

it('factCheckVerdict renvoie le libellé et la teinte lus dans FACT_CHECK_VERDICTS, jamais recopiés', function () {
    $article = fcmArticle(['fact_check_verdict' => 'presentation_trompeuse']);
    $expected = NewsArticle::FACT_CHECK_VERDICTS['presentation_trompeuse'];

    expect($article->factCheckVerdict())->toBe($expected);
});

it('le scope factChecked ne retourne que les fiches portant un verdict', function () {
    $verified = fcmArticle(['fact_check_verdict' => 'citation_inexacte']);
    fcmArticle(); // fiche ordinaire, sans vérification

    $ids = NewsArticle::factChecked()->pluck('id');

    expect($ids)->toHaveCount(1)
        ->and($ids->first())->toBe($verified->id);
});

// ── C. Rendu public du badge (fact-check-badge.blade.php via public\show.blade.php) ────

it('affiche le badge de vérification et le libellé du verdict sur une fiche vérifiée', function () {
    $article = fcmPublishedArticle('badge-present', [
        'fact_check_verdict' => 'citation_inexacte',
        'fact_check_claim' => "La citation attribuée au ministre n'existe pas dans le discours original.",
        'fact_check_source' => 'https://x.com/exemple/status/111',
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('nw-factcheck', false)
        ->assertSee('Vérification :', false)
        ->assertSee('Citation inexacte', false);
});

it('n\'affiche rien du vocabulaire de vérification sur une fiche ordinaire', function () {
    $article = fcmPublishedArticle('badge-absent');

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertDontSee('nw-factcheck', false)
        ->assertDontSee('Vérification :', false);
});

// ── D. Balisage JSON-LD ClaimReview (JsonLdService::claimReview()) ─────────────────────

it('inclut le balisage ClaimReview sur une fiche vérifiée', function () {
    $article = fcmPublishedArticle('claimreview-present', [
        'fact_check_verdict' => 'attribution_erronee',
        'fact_check_claim' => 'Les propos existent, mais pas sous cette forme.',
        'fact_check_source' => 'https://x.com/exemple/status/222',
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()->assertSee('"@type": "ClaimReview"', false);
});

it('omet le balisage ClaimReview sur une fiche ordinaire', function () {
    $article = fcmPublishedArticle('claimreview-absent');

    $response = $this->get(route('news.show', $article));

    $response->assertOk()->assertDontSee('"@type": "ClaimReview"', false);
});

// ── E. Route publique dédiée /verifications ─────────────────────────────────────────────

it('la route /verifications répond 200 et ne liste que les fiches vérifiées', function () {
    $verified = fcmPublishedArticle('verif-listee', [
        'fact_check_verdict' => 'contexte_manquant',
        'fact_check_claim' => 'Affirmation vérifiée pour la liste.',
    ]);
    $ordinary = fcmPublishedArticle('verif-ordinaire');

    $response = $this->get(route('news.verifications'));

    $response->assertOk()
        ->assertSee($verified->title, false)
        ->assertDontSee($ordinary->title, false);
});

// ── F. Endpoint admin marquer-relu (NewsCompositionController::markReviewed()) ─────────
// Hors du périmètre fact_check à proprement parler, mais demandé par le superviseur : ce
// contrat HTTP (route admin.news.composition.mark-reviewed) n'avait aucun test.

it('l\'endpoint admin marquer-relu renvoie reviewed_at et reviewed_by', function () {
    $admin = fcmAdmin();
    $article = fcmPublishedArticle('marquer-relu-http');

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.mark-reviewed', $article));

    $response->assertOk()->assertJsonStructure(['success', 'reviewed_at', 'reviewed_by']);
    expect($response->json('reviewed_at'))->not->toBeNull()
        ->and($response->json('reviewed_by'))->not->toBeEmpty();

    // Second appel sur la MÊME fiche : elle porte déjà une signature, l'endpoint refuse
    // (409) plutôt que de la reposer silencieusement une seconde fois.
    $second = $this->actingAs($admin)->postJson(route('admin.news.composition.mark-reviewed', $article));
    $second->assertStatus(409);
});
