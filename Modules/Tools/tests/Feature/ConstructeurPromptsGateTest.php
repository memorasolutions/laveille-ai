<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Régression de la passe adversariale (2026-07-26) : le gate is_under_construction de
// /outils/constructeur-prompts ne couvrait pas ses routes satellites (bibliothèque "Mes
// prompts", API prompts/tool-preferences) - un utilisateur non-admin pouvait les utiliser
// pendant la révision. Ces tests couvrent EnsureToolNotUnderConstruction sur les 3 points
// de fuite trouvés, + confirment qu'un autre outil (minuteur-visuel) n'est pas affecté.

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);

    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => true,
        'category' => 'productivite',
    ]);
});

it('blocks a non-superadmin from the constructeur-prompts TOOL PAGE itself while under revision (round 106, 2026-08-01)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/outils/constructeur-prompts');

    $response->assertOk();
    $response->assertSee('id="uc-title"', escape: false);
});

it('allows a non-admin authenticated user to READ their own prompt library on /user/prompts while under revision (round 106, 2026-08-01 : ce gate empêchait l\'accès à des données déjà confiées, pas seulement l\'usage de l\'outil)', function () {
    $user = User::factory()->create();

    \Modules\Tools\Models\SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Mon prompt déjà sauvegardé',
        'prompt_text' => 'Texte qui doit rester accessible pendant la révision',
    ]);

    $response = $this->actingAs($user)->get('/user/prompts');

    // La vraie page bibliothèque est rendue (pas le placeholder under-construction).
    $response->assertOk();
    $response->assertDontSee('id="uc-title"', escape: false);
    $response->assertSee('Rechercher dans mes prompts', escape: false);
});

it('blocks a non-admin authenticated user from the prompts API while under construction', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/prompts', [
        'name' => 'Test',
        'prompt_text' => 'Un texte de test',
    ]);

    $response->assertStatus(403);
    expect(\Modules\Tools\Models\SavedPrompt::count())->toBe(0);
});

it('blocks a non-admin authenticated user from constructeur-prompts tool-preferences while under construction', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/constructeur-prompts', [
        'key' => 'custom_cards',
        'value' => [],
    ]);

    $response->assertStatus(403);
});

it('allows a superadmin to use all constructeur-prompts satellite routes while under construction', function () {
    // isSuperAdmin() exige à la fois le rôle ET l'email exact de config('app.superadmin_email').
    $user = User::factory()->create(['email' => config('app.superadmin_email')]);
    $user->assignRole('super_admin');

    $this->actingAs($user)->get('/user/prompts')->assertOk();

    $store = $this->actingAs($user)->postJson('/api/prompts', [
        'name' => 'Test admin',
        'prompt_text' => 'Un texte de test',
    ]);
    $store->assertCreated();

    $this->actingAs($user)->postJson('/api/tool-preferences/constructeur-prompts', [
        'key' => 'custom_cards',
        'value' => [],
    ])->assertOk();
});

it('does not affect another tool (minuteur-visuel) not under construction', function () {
    Tool::firstOrCreate(['slug' => 'minuteur-visuel'], [
        'name' => 'Minuteur visuel',
        'description' => 'Test',
        'icon' => '⏱️',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'custom_colors',
        'value' => ['#991B1B'],
    ])->assertOk();
});

// Deux tests retires ici le 2026-08-02 (etape 9 de la refonte, .outils/PLAN-CONSTRUCTEUR-PROMPTS-
// ULTRA-2026-08-02.md) : ils verrouillaient l'i18n de window.promptBuilderConfig (n'existe plus)
// et de chaines retirees par le plan (« Ajouter une carte », « Verbe d'action », « Ameliorer avec
// mon IA »). Meme categorie RETIRE que les fichiers RoundNN purges - fonctionnalites disparues,
// pas un simple changement de markup.

it('shows the "Mes prompts" menu link to a non-admin while the tool is under revision (round 106, 2026-08-01 : la bibliothèque de lecture est accessible, le lien doit donc l\'être aussi - round 4 le masquait à tort)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('user.dashboard'));

    $response->assertOk();
    $response->assertSee('Mes prompts', escape: false);
});

it('shows the "Mes prompts" menu link to the superadmin while the tool is under construction', function () {
    $user = User::factory()->create(['email' => config('app.superadmin_email')]);
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->get(route('user.dashboard'));

    $response->assertOk();
    $response->assertSee('Mes prompts', escape: false);
});

it('shows saved prompt names/previews on /user/saved for a non-admin while the tool is under revision (round 106, 2026-08-01 : le round 11 masquait à tort une donnée déjà confiée par la personne, pas une fonctionnalité de l\'outil)', function () {
    $user = User::factory()->create();

    \Modules\Tools\Models\SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Nom de prompt visible',
        'prompt_text' => 'Texte de prompt qui doit rester accessible',
    ]);

    $response = $this->actingAs($user)->get(route('user.saved'));

    $response->assertOk();
    $response->assertSee('Nom de prompt visible', escape: false);
});

it('still shows other saved item types on /user/saved while constructeur-prompts is under construction', function () {
    $user = User::factory()->create();

    \Modules\Tools\Models\SavedQrPreset::create([
        'user_id' => $user->id,
        'name' => 'Mon QR visible',
        'config_text' => '{}',
    ]);

    $response = $this->actingAs($user)->get(route('user.saved'));

    $response->assertOk();
    $response->assertSee('Mon QR visible', escape: false);
});

it('shows saved prompt names/previews on /user/saved to the superadmin while the tool is under construction', function () {
    $user = User::factory()->create(['email' => config('app.superadmin_email')]);
    $user->assignRole('super_admin');

    \Modules\Tools\Models\SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Nom de prompt admin',
        'prompt_text' => 'Texte visible pour le superadmin',
    ]);

    $response = $this->actingAs($user)->get(route('user.saved'));

    $response->assertOk();
    $response->assertSee('Nom de prompt admin', escape: false);
});

it('includes saved prompts in /user/data-export for a non-admin while the tool is under revision (round 106, 2026-08-01 : bloquer l\'export RGPD de ses propres données n\'est jamais acceptable, même outil en révision - round 12 excluait à tort cette donnée)', function () {
    $user = User::factory()->create();

    \Modules\Tools\Models\SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt export attendu',
        'prompt_text' => 'Texte complet qui doit être exportable via le droit RGPD',
    ]);

    $response = $this->actingAs($user)->get(route('user.data-export'));

    $response->assertOk();
    $response->assertJsonFragment(['name' => 'Prompt export attendu']);
});

it('includes saved prompts in /user/data-export for the superadmin while the tool is under construction', function () {
    $user = User::factory()->create(['email' => config('app.superadmin_email')]);
    $user->assignRole('super_admin');

    \Modules\Tools\Models\SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt export admin',
        'prompt_text' => 'Texte visible pour le superadmin',
    ]);

    $response = $this->actingAs($user)->get(route('user.data-export'));

    $response->assertOk();
    $response->assertJsonFragment(['name' => 'Prompt export admin']);
});

it('returns the "under construction" JSON message localized in English (round 31, 2026-07-27: __() absent des 2 lang files, un anglophone recevait le message en français)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->withSession(['locale' => 'en'])->postJson('/api/prompts', [
        'name' => 'Test',
        'prompt_text' => 'Un texte de test',
    ]);

    $response->assertStatus(403);
    expect($response->json('message'))->toBe('This tool is temporarily unavailable.');
});

it('renders the "Constructeur de prompts" tool_name label localized in English on /user/saved (round 31, 2026-07-27: __() absent des 2 lang files)', function () {
    Tool::where('slug', 'constructeur-prompts')->update(['is_under_construction' => false]);

    $user = User::factory()->create();

    \Modules\Tools\Models\SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt saved',
        'prompt_text' => 'Some text',
    ]);

    $this->actingAs($user)->withSession(['locale' => 'en'])
        ->get(route('user.saved'))
        ->assertOk()
        ->assertSee('Prompt Builder', escape: false)
        ->assertSee('Prompts', escape: false)
        ->assertSee('Load', escape: false)
        ->assertDontSee('Constructeur de prompts', escape: false)
        ->assertDontSee('Charger', escape: false);
});

it('renders the /user/saved empty-state text localized in English (round 32, 2026-07-27: 7 __() de la vue saved/index.blade.php elle-même - pas du contrôleur - absents des 2 lang files)', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->withSession(['locale' => 'en'])
        ->get(route('user.saved'))
        ->assertOk()
        ->assertSee('Your saved tool configurations.', escape: false)
        ->assertSee('No saved configurations', escape: false)
        ->assertSee('Use the tools and save your configurations to find them here.', escape: false)
        ->assertSee('Explore tools', escape: false)
        ->assertDontSee('Vos configurations d', escape: false)
        ->assertDontSee('Aucune sauvegarde', escape: false);
});

it('does not re-query the tools schema on every Tool::isAccessibleTo() call (round 33, 2026-07-27: Schema::hasTable()/hasColumn() relançaient 2 requêtes information_schema à chaque appel, jamais mises en cache)', function () {
    DB::enableQueryLog();

    Tool::isAccessibleTo('constructeur-prompts', null);
    $afterFirstCall = count(DB::getQueryLog());

    Tool::isAccessibleTo('constructeur-prompts', null);
    Tool::isAccessibleTo('constructeur-prompts', null);
    $afterThreeMoreCalls = count(DB::getQueryLog());

    // Les 2 requêtes information_schema (hasTable + hasColumn) ne doivent apparaître qu'une
    // seule fois au total - les appels suivants ne doivent générer que la requête `tools`
    // (1 par appel, puisque $tool n'est pas fourni ici).
    expect($afterThreeMoreCalls - $afterFirstCall)->toBe(2);
});

it('renders /user/saved with a delete handler that checks r.ok before removing the row and shows an error toast on failure (round 39, 2026-07-27: window.deleteSavedItem() retirait la ligne du DOM inconditionnellement, faisant disparaitre visuellement un prompt/sauvegarde meme quand le DELETE serveur echouait - 403/404/429/500)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('user.saved'));

    $response->assertOk();
    $html = $response->getContent();

    // Le fetch DELETE ne doit retirer la ligne QUE dans la branche r.ok - jamais inconditionnellement
    // dans un simple .then(function() { ... row.remove() ... }) sans vérification de statut.
    expect($html)->toContain('function(r) {');
    expect($html)->toContain('if (r.ok) {');
    expect($html)->toContain('window.toast');
    // Garde-fou anti-régression textuelle : l'ancien pattern fautif (retrait direct dans le tout
    // premier .then sans paramètre de réponse) ne doit plus apparaitre.
    expect($html)->not->toContain(".then(function() { if (row) row.remove(); });");
});

// Round 106 (2026-08-01) : correctif du gate de révision — l'outil étant EN RÉVISION
// (is_under_construction = true, cf. beforeEach), un non-superadmin doit pouvoir LIRE,
// EXPORTER et SUPPRIMER ses propres prompts déjà sauvegardés, mais jamais en créer/modifier
// de nouveaux via l'API, ni voir les prompts d'un autre compte (anti-IDOR).

it('allows an authenticated non-admin to list their own prompts via GET /api/prompts while under revision', function () {
    $user = User::factory()->create();

    \Modules\Tools\Models\SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt listé',
        'prompt_text' => 'Texte',
    ]);

    $response = $this->actingAs($user)->getJson('/api/prompts');

    $response->assertOk();
    $response->assertJsonFragment(['name' => 'Prompt listé']);
});

it('allows an authenticated non-admin to read ONE of their own prompts via GET /api/prompts/{id} while under revision', function () {
    $user = User::factory()->create();

    $prompt = \Modules\Tools\Models\SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt individuel',
        'prompt_text' => 'Texte',
    ]);

    $response = $this->actingAs($user)->getJson('/api/prompts/'.$prompt->public_id);

    $response->assertOk();
    $response->assertJsonFragment(['name' => 'Prompt individuel']);
});

it('allows an authenticated non-admin to launch their RGPD data export while under revision', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('user.data-export'));

    $response->assertOk();
    $response->assertJsonStructure(['profile', 'export_date']);
});

it('allows an authenticated non-admin to delete (right to erasure) one of their own prompts via DELETE /api/prompts/{id} while under revision', function () {
    $user = User::factory()->create();

    $prompt = \Modules\Tools\Models\SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt à effacer',
        'prompt_text' => 'Texte',
    ]);

    $response = $this->actingAs($user)->deleteJson('/api/prompts/'.$prompt->public_id);

    $response->assertStatus(204);
    expect(\Modules\Tools\Models\SavedPrompt::find($prompt->id))->toBeNull();
});

it('still blocks a non-admin from CREATING a new prompt via POST /api/prompts while under revision (only reading/deleting own data is exempted, not usage of the tool)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/prompts', [
        'name' => 'Nouveau prompt',
        'prompt_text' => 'Texte',
    ]);

    $response->assertStatus(403);
    expect(\Modules\Tools\Models\SavedPrompt::where('name', 'Nouveau prompt')->count())->toBe(0);
});

it('anti-IDOR: an authenticated non-admin CANNOT read another user\'s prompt via GET /api/prompts/{id} while under revision', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $promptA = \Modules\Tools\Models\SavedPrompt::create([
        'user_id' => $userA->id,
        'name' => 'Prompt privé de A',
        'prompt_text' => 'Texte privé',
    ]);

    $response = $this->actingAs($userB)->getJson('/api/prompts/'.$promptA->public_id);

    $response->assertStatus(404);
});

it('anti-IDOR: an authenticated non-admin CANNOT delete another user\'s prompt via DELETE /api/prompts/{id} while under revision', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $promptA = \Modules\Tools\Models\SavedPrompt::create([
        'user_id' => $userA->id,
        'name' => 'Prompt privé de A',
        'prompt_text' => 'Texte privé',
    ]);

    $response = $this->actingAs($userB)->deleteJson('/api/prompts/'.$promptA->public_id);

    $response->assertStatus(404);
    expect(\Modules\Tools\Models\SavedPrompt::find($promptA->id))->not->toBeNull();
});

it('anti-IDOR: another user\'s prompts never appear in GET /api/prompts index while under revision', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    \Modules\Tools\Models\SavedPrompt::create([
        'user_id' => $userA->id,
        'name' => 'Prompt privé de A',
        'prompt_text' => 'Texte privé',
    ]);

    $response = $this->actingAs($userB)->getJson('/api/prompts');

    $response->assertOk();
    $response->assertJsonMissing(['name' => 'Prompt privé de A']);
});

it('anti-IDOR: another user\'s prompt never appears in /user/saved nor in the RGPD export of a different account while under revision', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    \Modules\Tools\Models\SavedPrompt::create([
        'user_id' => $userA->id,
        'name' => 'Prompt privé de A pour saved/export',
        'prompt_text' => 'Texte privé',
    ]);

    $this->actingAs($userB)->get(route('user.saved'))
        ->assertOk()
        ->assertDontSee('Prompt privé de A pour saved/export', escape: false);

    $this->actingAs($userB)->get(route('user.data-export'))
        ->assertOk()
        ->assertJsonMissing(['name' => 'Prompt privé de A pour saved/export']);
});

it('blocks a guest (unauthenticated) from every prompt-access route while under revision', function () {
    $this->get('/user/prompts')->assertRedirect(route('login'));
    $this->getJson('/api/prompts')->assertStatus(401);
    $this->get(route('user.saved'))->assertRedirect(route('login'));
    $this->get(route('user.data-export'))->assertRedirect(route('login'));

    $prompt = \Modules\Tools\Models\SavedPrompt::create([
        'user_id' => User::factory()->create()->id,
        'name' => 'Prompt quelconque',
        'prompt_text' => 'Texte',
    ]);
    $this->getJson('/api/prompts/'.$prompt->public_id)->assertStatus(401);
    $this->deleteJson('/api/prompts/'.$prompt->public_id)->assertStatus(401);
});
