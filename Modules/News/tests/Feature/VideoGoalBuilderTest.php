<?php

declare(strict_types=1);

/**
 * Tests admin : Générateur d'objectif vidéo (superadmin uniquement).
 *
 * Couvre : accès (invité / non-admin / superadmin), récupération JSON des actualités publiées
 * sur une plage de dates, validation de la plage, génération IA (service mocké) et ses replis.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AI\Services\AiService;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux ───────────────────────────────────────────────────────────

function vgbSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source objectif vidéo',
        'url' => 'https://vgb-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function vgbArticle(int $sourceId, array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title' => "Article objectif vidéo {$i}",
        'guid' => "guid-vgb-{$suffix}",
        'url' => "https://exemple.com/vgb-{$suffix}",
        'description' => "Description de test {$i}",
        'summary' => "Résumé de test {$i}",
        'slug' => "article-vgb-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

/**
 * Superadmin réel : email superadmin (config('app.superadmin_email')) + rôle 'super_admin'
 * (underscore) — seule combinaison acceptée par User::isSuperAdmin(), source unique de vérité
 * utilisée par Modules\Authors\Http\Middleware\EnsureSuperAdmin::handle() depuis son correctif
 * du 403 production (l'ancienne vérification 'super-admin'/'admin' du middleware ne correspondait
 * à aucun rôle réellement assigné par le seeder, cf. database/seeders/DatabaseSeeder.php).
 */
function vgbSuperAdmin(): \App\Models\User
{
    $user = \App\Models\User::factory()->create([
        'email' => config('app.superadmin_email'),
    ]);
    $user->assignRole('super_admin');

    return $user;
}

/**
 * Utilisateur connecté SANS rôle admin. Force l'environnement hors local/testing pour désactiver
 * de façon déterministe le repli « id===1 » d'EnsureSuperAdmin (sqlite :memory: + RefreshDatabase
 * repart l'auto-increment à 1 à chaque test — sans ce forçage, ce test serait fragile selon
 * l'ordre d'exécution).
 */
function vgbRegularUser(): \App\Models\User
{
    app()->detectEnvironment(fn () => 'production');

    return \App\Models\User::factory()->create();
}

// ── Accès ─────────────────────────────────────────────────────────────────────

it('redirects guest to login', function () {
    $response = $this->get(route('admin.news.video-goal.index'));

    $response->assertRedirect(route('login'));
});

it('blocks non-superadmin from index', function () {
    $user = vgbRegularUser();

    $response = $this->actingAs($user)->get(route('admin.news.video-goal.index'));

    $response->assertStatus(403);
});

it('allows superadmin to view index', function () {
    $admin = vgbSuperAdmin();

    $response = $this->actingAs($admin)->get(route('admin.news.video-goal.index'));

    $response->assertOk();
    $response->assertSee("Générateur d'objectif vidéo", false);
    $response->assertSee('vgb-date-start', false);
    $response->assertSee('vgb-date-end', false);
    $response->assertSee('videoGoalBuilder(', false);
    $response->assertSee('Charger les actualités', false);
});

// ── Ordre de chargement du mixin partagé (ticket #2210, 2026-09-05) ────────────
// La PRÉSENCE de news-article-picker.js et de l'appel videoGoalBuilder(...) dans le HTML ne prouve
// rien : les deux étaient déjà présents AVANT le correctif, et l'écran cascadait quand même en
// ReferenceError (le script chargeait APRÈS que Livewire ait déjà démarré Alpine et évalué
// x-data). La preuve porte sur la POSITION RELATIVE dans le HTML rendu : le script doit arriver
// avant le code qui s'en sert ET avant le script Livewire qui démarre Alpine
// (@livewireScripts, Modules/Backoffice/.../layouts/admin.blade.php:180).
it('news-article-picker.js charge avant l\'appel videoGoalBuilder(...) et avant le script Livewire qui démarre Alpine', function () {
    $admin = vgbSuperAdmin();

    $html = $this->actingAs($admin)->get(route('admin.news.video-goal.index'))->getContent();

    $scriptPos = strpos($html, 'news-article-picker.js');
    $factoryCallPos = strpos($html, 'videoGoalBuilder(');
    $livewireBootPos = strpos($html, 'livewire.js');

    expect($scriptPos)->not->toBeFalse('news-article-picker.js absent du HTML rendu');
    expect($factoryCallPos)->not->toBeFalse('appel videoGoalBuilder(...) absent du HTML rendu');
    expect($livewireBootPos)->not->toBeFalse('script livewire.js absent du HTML rendu');

    expect($scriptPos)->toBeLessThan($factoryCallPos);
    expect($scriptPos)->toBeLessThan($livewireBootPos);
});

// ── Non-régression du piège trouvé pendant l'implémentation (ticket #2210) ─────
// La première version du correctif écrivait le mot "@assets" dans un commentaire JAVASCRIPT
// (// ...) à l'intérieur d'un <script> : Blade ne distingue pas un commentaire JS d'un commentaire
// Blade ({{-- --}}) et compile @assets où qu'il apparaisse hors {{-- --}}, ce qui a posé un
// ob_start() jamais fermé (PHPUnit : "did not close its own output buffers"). Sans ce test, la
// prochaine personne qui écrit "@assets" dans un commentaire JS refait exactement la même erreur
// sans que rien ne l'arrête.
it('ne laisse aucun buffer de sortie ouvert après le rendu du générateur d\'objectif vidéo', function () {
    $admin = vgbSuperAdmin();

    $obLevelBefore = ob_get_level();
    $response = $this->actingAs($admin)->get(route('admin.news.video-goal.index'));
    $obLevelAfter = ob_get_level();

    $response->assertOk();
    expect($obLevelAfter)->toBe($obLevelBefore);
});

// ── newsForRange (JSON) ───────────────────────────────────────────────────────

it('returns published articles within date range as json', function () {
    $admin = vgbSuperAdmin();
    $source = vgbSource();

    $inRange1 = vgbArticle($source->id, ['pub_date' => now()->subDays(1), 'is_published' => true]);
    $inRange2 = vgbArticle($source->id, ['pub_date' => now()->subDays(2), 'is_published' => true]);
    $unpublished = vgbArticle($source->id, ['pub_date' => now()->subDays(1), 'is_published' => false]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.video-goal.news'), [
        'date_start' => now()->subDays(5)->toDateString(),
        'date_end' => now()->addDay()->toDateString(),
    ]);

    $response->assertOk();
    expect($response->json('count'))->toBe(2);

    $ids = collect($response->json('items'))->pluck('id')->all();
    expect($ids)->toContain($inRange1->id)
        ->toContain($inRange2->id)
        ->not->toContain($unpublished->id);
});

it('validates date range input', function () {
    $admin = vgbSuperAdmin();

    // date_end manquant
    $this->actingAs($admin)->postJson(route('admin.news.video-goal.news'), [
        'date_start' => now()->toDateString(),
    ])->assertStatus(422);

    // date_end avant date_start
    $this->actingAs($admin)->postJson(route('admin.news.video-goal.news'), [
        'date_start' => now()->toDateString(),
        'date_end' => now()->subDay()->toDateString(),
    ])->assertStatus(422);

    // plage > 90 jours (validation OK mais rejet applicatif dans le contrôleur)
    $this->actingAs($admin)->postJson(route('admin.news.video-goal.news'), [
        'date_start' => now()->subDays(120)->toDateString(),
        'date_end' => now()->toDateString(),
    ])->assertStatus(422);
});

// ── generateGoal ───────────────────────────────────────────────────────────────

it('generates a goal from selected articles', function () {
    $admin = vgbSuperAdmin();
    $source = vgbSource();
    $article = vgbArticle($source->id);

    $this->mock(AiService::class, function ($mock) {
        $mock->shouldReceive('chatWithHistory')
            ->once()
            ->andReturn('Objectif de vidéo généré pour le test.');
    });

    $response = $this->actingAs($admin)->postJson(route('admin.news.video-goal.generate'), [
        'article_ids' => [$article->id],
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'goal' => 'Objectif de vidéo généré pour le test.',
        'article_count' => 1,
    ]);
});

it('rejects generate request with no article ids', function () {
    $admin = vgbSuperAdmin();

    $response = $this->actingAs($admin)->postJson(route('admin.news.video-goal.generate'), []);

    $response->assertStatus(422);
});

it('rejects generate request with nonexistent article ids', function () {
    $admin = vgbSuperAdmin();

    $response = $this->actingAs($admin)->postJson(route('admin.news.video-goal.generate'), [
        'article_ids' => [999999],
    ]);

    $response->assertStatus(422);
});

// ── Choix du format : Prompteur ou Carrousel (ticket #2564, 2026-09-14) ───────
// Demande du fondateur, verbatim : « ok, mais sous forme de carroussel (choisir ça ou le
// prompteur) choix que je vais faire ».

it("génère un carrousel quand le format 'carrousel' est demandé", function () {
    $admin = vgbSuperAdmin();
    $source = vgbSource();
    $article = vgbArticle($source->id);

    $this->mock(AiService::class, function ($mock) {
        $mock->shouldReceive('chatWithHistory')
            ->once()
            ->andReturn("DIAPO 1 - OUVERTURE\nTrois nouvelles cette semaine.");
    });

    $response = $this->actingAs($admin)->postJson(route('admin.news.video-goal.generate'), [
        'article_ids' => [$article->id],
        'format' => 'carrousel',
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'format' => 'carrousel',
        'article_count' => 1,
    ]);
    expect($response->json('goal'))->toContain('DIAPO 1');
});

// L'existant ne doit RIEN changer pour qui ne choisit rien : le champ est facultatif et son
// absence vaut « prompteur », exactement comme avant l'ajout du choix.
it("retombe sur le prompteur quand aucun format n'est transmis", function () {
    $admin = vgbSuperAdmin();
    $source = vgbSource();
    $article = vgbArticle($source->id);

    $this->mock(AiService::class, function ($mock) {
        $mock->shouldReceive('chatWithHistory')->once()->andReturn('Un paragraphe.');
    });

    $response = $this->actingAs($admin)->postJson(route('admin.news.video-goal.generate'), [
        'article_ids' => [$article->id],
    ]);

    $response->assertOk();
    expect($response->json('format'))->toBe('prompteur');
});

it('refuse un format inconnu', function () {
    $admin = vgbSuperAdmin();
    $source = vgbSource();
    $article = vgbArticle($source->id);

    $this->actingAs($admin)->postJson(route('admin.news.video-goal.generate'), [
        'article_ids' => [$article->id],
        'format' => 'diaporama',
    ])->assertStatus(422);
});

/**
 * LE test qui porte la demande. Vérifier que la réponse renvoie l'étiquette « carrousel » ne
 * prouverait RIEN : un contrôleur qui appellerait deux fois la même génération et se contenterait
 * de changer l'étiquette passerait ce contrôle. La preuve doit porter sur ce qui est réellement
 * ENVOYÉ à l'IA - deux consignes différentes, sinon les deux sorties seraient les mêmes.
 */
it('envoie à l\'IA des consignes RÉELLEMENT différentes selon le format', function () {
    $admin = vgbSuperAdmin();
    $source = vgbSource();
    $article = vgbArticle($source->id);

    $captures = [];

    $this->mock(AiService::class, function ($mock) use (&$captures) {
        $mock->shouldReceive('chatWithHistory')
            ->twice()
            ->andReturnUsing(function (array $messages) use (&$captures) {
                $captures[] = $messages[0]['content'];

                return 'sortie quelconque';
            });
    });

    $this->actingAs($admin)->postJson(route('admin.news.video-goal.generate'), [
        'article_ids' => [$article->id],
        'format' => 'prompteur',
    ])->assertOk();

    $this->actingAs($admin)->postJson(route('admin.news.video-goal.generate'), [
        'article_ids' => [$article->id],
        'format' => 'carrousel',
    ])->assertOk();

    expect($captures)->toHaveCount(2);
    expect($captures[0])->not->toBe($captures[1]);

    // Et pas seulement « différentes » : la consigne du carrousel porte ce que l'autre n'a pas -
    // le découpage en diapositives et la borne de mots qui le rend lisible face caméra.
    expect($captures[1])->toContain('DIAPO 1');
    expect($captures[1])->toContain('20 mots');
    expect($captures[0])->not->toContain('DIAPO 1');
});

/**
 * Défaut évité pendant l'implémentation, verrouillé ici parce qu'il est invisible à la relecture :
 * la mécanique d'appel est PARTAGÉE entre les deux formats, et sa première version retournait la
 * constante de repli de l'objectif en dur. Un carrousel indisponible aurait donc affiché
 * « Impossible de générer l'objectif de vidéo », et le fondateur aurait cru s'être trompé de bouton.
 */
it('affiche le repli DU CARROUSEL quand le service IA est indisponible, jamais celui de l\'objectif', function () {
    $admin = vgbSuperAdmin();
    $source = vgbSource();
    $article = vgbArticle($source->id);

    $this->mock(AiService::class, function ($mock) {
        $mock->shouldReceive('chatWithHistory')
            ->once()
            ->andThrow(new \RuntimeException('service indisponible'));
    });

    $response = $this->actingAs($admin)->postJson(route('admin.news.video-goal.generate'), [
        'article_ids' => [$article->id],
        'format' => 'carrousel',
    ]);

    $response->assertOk();
    expect($response->json('goal'))
        ->toContain('carrousel')
        ->not->toContain("objectif de vidéo");
});

it('affiche les deux choix de format sur la page', function () {
    $admin = vgbSuperAdmin();

    $response = $this->actingAs($admin)->get(route('admin.news.video-goal.index'));

    $response->assertOk();
    $response->assertSee('value="prompteur"', false);
    $response->assertSee('value="carrousel"', false);
    $response->assertSee('Format de sortie', false);
});
