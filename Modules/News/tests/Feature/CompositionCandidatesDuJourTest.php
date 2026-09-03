<?php

declare(strict_types=1);

/**
 * L'écran de composition ne montre que les actualités collectées DANS LA JOURNÉE, et traduit en
 * français les titres venus de sources non francophones (demande du fondateur, 2026-08-23).
 *
 * Deux propriétés sont verrouillées ici, et ce sont les deux qui peuvent nuire si elles lâchent :
 *  - le filtre porte sur `created_at` (collecte) et non sur `pub_date` (date annoncée par la
 *    source) : un article daté d'hier soir mais récolté ce matin DOIT rester visible ;
 *  - une traduction mal alignée est pire qu'une absence de traduction, donc tout écart de compte
 *    doit rendre TOUS les originaux, jamais un mélange.
 *
 * RÉVISION 2026-08-24 (design doc, section traduction précalculée) : le plafond de 200 est
 * retiré (452 des 652 actualités du 23 août restaient invisibles derrière lui) et la traduction
 * lit désormais 'title_fr' en priorité (voir Modules\News\Console\TranslateTitlesCommand et
 * Modules\News\Http\Controllers\Admin\NewsCompositionController::titresTraduits()) - deux
 * propriétés supplémentaires verrouillées plus bas dans ce fichier.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Core\Services\TranslationService;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

function cdjAdmin(): \App\Models\User
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

function cdjSource(string $langue): NewsSource
{
    return NewsSource::firstOrCreate(
        ['url' => "https://cdj-{$langue}.exemple.com/rss"],
        ['name' => "Source cdj {$langue}", 'language' => $langue, 'active' => true]
    );
}

function cdjArticle(string $langue = 'fr', array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => cdjSource($langue)->id,
        'title' => "Titre cdj {$i}",
        'guid' => "guid-cdj-{$suffix}",
        'url' => "https://exemple.com/cdj-{$suffix}",
        'description' => '',
        'summary' => "Résumé cdj {$i}",
        'slug' => "cdj-{$suffix}",
        'pub_date' => now()->subMinutes($i),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));
}

it('ne renvoie que les actualités collectées aujourd\'hui', function () {
    $dujour = cdjArticle();
    $hier = cdjArticle();
    $hier->forceFill(['created_at' => now()->subDay()])->saveQuietly();

    $admin = cdjAdmin();
    $reponse = $this->actingAs($admin)->getJson(route('admin.news.composition.candidates'));

    $reponse->assertOk();
    $ids = collect($reponse->json('items'))->pluck('id')->all();

    expect($ids)->toContain($dujour->id)
        ->and($ids)->not->toContain($hier->id);
});

it('garde un article daté d\'hier mais collecté aujourd\'hui', function () {
    // Le piège que le filtre sur pub_date aurait créé : une source qui date son article de la
    // veille au soir. Il a été récolté ce matin, il doit rester composable.
    $article = cdjArticle('fr', ['pub_date' => now()->subDay()->setTime(21, 30)]);

    $admin = cdjAdmin();
    $reponse = $this->actingAs($admin)->getJson(route('admin.news.composition.candidates'));

    expect(collect($reponse->json('items'))->pluck('id')->all())->toContain($article->id);
});

it('signale explicitement quand il affiche un jour de repli plutôt que la journée en cours', function () {
    $ancien = cdjArticle();
    $ancien->forceFill(['created_at' => now()->subDays(4)])->saveQuietly();

    $admin = cdjAdmin();
    $reponse = $this->actingAs($admin)->getJson(route('admin.news.composition.candidates'));

    $reponse->assertOk()
        ->assertJsonPath('est_repli', true);
    expect(collect($reponse->json('items'))->pluck('id')->all())->toContain($ancien->id);
});

it('rend TOUS les originaux si le nombre de lignes traduites ne correspond pas', function () {
    config()->set('services.openrouter.api_key', 'cle-de-test');
    // Deux titres envoyés, une seule ligne rendue : correspondance impossible à établir.
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => "1. Un seul titre rendu"]]],
        ], 200),
    ]);

    $resultat = TranslationService::translateBatch(['First headline', 'Second headline']);

    expect($resultat['titres'])->toBe(['First headline', 'Second headline'])
        ->and($resultat['statut'])->toBe('indisponible');
});

it('rend les titres traduits et retire la numérotation quand le compte correspond', function () {
    config()->set('services.openrouter.api_key', 'cle-de-test');
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => "1. Premier titre\n2. Deuxième titre"]]],
        ], 200),
    ]);

    $resultat = TranslationService::translateBatch(['First headline', 'Second headline']);

    expect($resultat['titres'])->toBe(['Premier titre', 'Deuxième titre'])
        ->and($resultat['statut'])->toBe('ok');
});

it('signale l\'indisponibilité quand le fournisseur refuse, sans perdre les titres', function () {
    config()->set('services.openrouter.api_key', 'cle-de-test');
    // Le cas qui a immobilisé l'enrichissement de l'annuaire neuf jours : un refus de fournisseur.
    Http::fake(['openrouter.ai/*' => Http::response(['error' => 'no endpoints'], 404)]);

    $resultat = TranslationService::translateBatch(['First headline']);

    expect($resultat['titres'])->toBe(['First headline'])
        ->and($resultat['statut'])->toBe('indisponible')
        ->and($resultat['motif'])->not->toBeNull();
});

// ── Retrait du plafond de 200 + lecture prioritaire de title_fr (2026-08-24) ──────────────

it('renvoie plus de 200 actualités quand plus de 200 ont été collectées aujourd\'hui', function () {
    // C'est LE test qui prouve la demande du propriétaire : 452 des 652 actualités du 23 août
    // restaient invisibles derrière l'ancien plafond de 200.
    foreach (range(1, 205) as $i) {
        cdjArticle();
    }

    $admin = cdjAdmin();
    $reponse = $this->actingAs($admin)->getJson(route('admin.news.composition.candidates'));

    $reponse->assertOk();
    expect(count($reponse->json('items')))->toBeGreaterThan(200);
});

it('préfère title_fr à title quand présent, et seo_title à tout le reste', function () {
    // title_fr déjà écrit par la commande planifiée : aucun appel réseau nécessaire pour
    // l'afficher, contrairement à l'ancien comportement qui retraduisait tout à la volée.
    $avecTitleFr = cdjArticle('en', [
        'title' => 'English original',
        'title_fr' => 'Déjà traduit en base',
        'title_fr_at' => now(),
    ]);
    // seo_title est une réécriture éditoriale : elle prime, et l'article n'entre même pas dans
    // le lot à traduire.
    $avecSeoTitle = cdjArticle('en', [
        'title' => 'English original 2',
        'seo_title' => 'Titre éditorial',
    ]);

    $admin = cdjAdmin();
    $reponse = $this->actingAs($admin)->getJson(route('admin.news.composition.candidates'));

    $reponse->assertOk();
    $items = collect($reponse->json('items'))->keyBy('id');

    expect($items[$avecTitleFr->id]['title'])->toBe('Déjà traduit en base')
        ->and($items[$avecSeoTitle->id]['title'])->toBe('Titre éditorial');
});

/**
 * AJOUT 2026-09-03 (ticket #2208) - la frontière entre « brouillon brut » et « travail éditorial ».
 *
 * Le filtre du jour est une demande explicite du fondateur (2026-08-23) et il reste intact pour
 * les CANDIDATS BRUTS. Mais une fiche sur laquelle un humain a déjà travaillé n'est pas un
 * candidat : c'est du travail en cours, et le faire disparaître de l'écran le fait perdre.
 *
 * La définition de « fiche à valeur » n'est pas inventée ici : c'est EXACTEMENT celle que
 * Modules\News\Console\PruneDraftsCommand refuse déjà de supprimer (is_published faux, retired_at
 * nul, reviewed_at nul et hasComposedSummary() faux). Ce que la purge protège, l'écran doit le
 * montrer - sinon le site protège un travail que son auteur ne peut plus retrouver.
 */
it('garde visible un brouillon COMPOSÉ un autre jour, même quand la journée courante a des articles', function () {
    // La journée courante n'est PAS vide : le repli automatique ne peut donc pas jouer.
    $dujour = cdjArticle();

    $composeHier = cdjArticle();
    $composeHier->forceFill([
        'created_at' => now()->subDays(3),
        'structured_summary' => ['composed' => true, 'hook' => 'Travail commencé avant-hier.'],
    ])->saveQuietly();

    $admin = cdjAdmin();
    $reponse = $this->actingAs($admin)->getJson(route('admin.news.composition.candidates'));

    $reponse->assertOk();
    $ids = collect($reponse->json('items'))->pluck('id')->all();

    expect($ids)->toContain($dujour->id)
        ->and($ids)->toContain($composeHier->id);
});

it('garde visible un brouillon RELU un autre jour', function () {
    cdjArticle(); // la journée courante n'est pas vide

    $reluHier = cdjArticle();
    $reluHier->forceFill([
        'created_at' => now()->subDays(2),
        'reviewed_at' => now()->subDays(2),
    ])->saveQuietly();

    $admin = cdjAdmin();
    $reponse = $this->actingAs($admin)->getJson(route('admin.news.composition.candidates'));

    expect(collect($reponse->json('items'))->pluck('id')->all())->toContain($reluHier->id);
});

it('marque les fiches hors du jour affiché, pour que l\'écran puisse le dire', function () {
    cdjArticle();

    $composeHier = cdjArticle();
    $composeHier->forceFill([
        'created_at' => now()->subDays(3),
        'structured_summary' => ['composed' => true, 'hook' => 'Travail commencé avant-hier.'],
    ])->saveQuietly();

    $admin = cdjAdmin();
    $items = collect($this->actingAs($admin)->getJson(route('admin.news.composition.candidates'))->json('items'));

    // L'assertion porte sur LA fiche concernée, jamais sur la page entière : une clé présente
    // ailleurs dans la réponse ne doit pas pouvoir faire passer ce test.
    $fiche = $items->firstWhere('id', $composeHier->id);
    expect($fiche)->not->toBeNull()
        ->and($fiche['hors_jour'] ?? null)->toBeTrue();
});

it('ne ressuscite PAS un brouillon BRUT d\'un autre jour (la demande « du jour seulement » tient)', function () {
    cdjArticle();

    $brutHier = cdjArticle();
    $brutHier->forceFill(['created_at' => now()->subDays(3)])->saveQuietly();

    $admin = cdjAdmin();
    $ids = collect($this->actingAs($admin)->getJson(route('admin.news.composition.candidates'))->json('items'))->pluck('id')->all();

    expect($ids)->not->toContain($brutHier->id);
});
