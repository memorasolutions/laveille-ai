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

it('blocks a non-admin authenticated user from /user/prompts while under construction', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/user/prompts');

    // Marqueur commun aux 2 modes (construction/révision) de tools::public.under-construction -
    // confirme que la vraie page "Mes prompts" (bouton "Ajouter une carte" n'existe pas ici, mais
    // le titre id="uc-title" oui) n'a PAS été rendue.
    $response->assertOk();
    $response->assertSee('id="uc-title"', escape: false);
    $response->assertDontSee('Rechercher dans mes prompts', escape: false);
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

it('renders all 12 window.promptBuilderConfig.i18n keys as real localized HTML on the constructeur-prompts page (round 28 covered 2/12, round 29, 2026-07-27, étend aux 10 restantes - dont 5 messages d\'erreur critiques saveError/loadError/exportError/deleteError/importError - jamais exercées par un test de rendu HTTP réel dans les 2 langues)', function () {
    Tool::where('slug', 'constructeur-prompts')->update(['is_under_construction' => false]);

    $user = User::factory()->create();

    // Note : @json() échappe unicode/apostrophe/slash de façon non triviale (JSON_HEX_APOS
    // notamment) - plutôt que de figer un format d'échappement exact et fragile, on normalise
    // le HTML rendu (dé-échappe é, ’, …, ', \/) avant de chercher le texte
    // clair attendu - robuste peu importe la forme d'échappement réellement produite.
    $normalize = fn (string $html): string => str_replace(
        [
            chr(92) . 'u00e9',
            chr(92) . 'u2019',
            chr(92) . 'u2026',
            chr(92) . 'u0027',
            chr(92) . '/',
        ],
        ['é', "'", '…', "'", '/'],
        $html
    );

    $fr = $normalize($this->actingAs($user)->withSession(['locale' => 'fr'])
        ->get('/outils/constructeur-prompts')
        ->assertOk()
        ->getContent());

    expect($fr)
        ->toContain('promptCopied: "Prompt copié"')
        ->toContain('promptSaved: "Prompt sauvegardé"')
        ->toContain('saveError: "Erreur de sauvegarde. Réessayez."')
        ->toContain('loadError: "Impossible de charger ce prompt pour édition."')
        ->toContain('exportError: "Impossible d\'exporter le fichier. Réessayez."')
        ->toContain('deleteError: "Erreur lors de la suppression. Réessayez."')
        ->toContain('importError: "Erreur lors de l\'importation. Réessayez."')
        ->toContain('openInGeneric: "Prompt copié : ouverture de la conversation…"')
        ->toContain('openInGemini: "Prompt copié : colle-le dans Gemini (Ctrl/Cmd + V)."')
        ->toContain('openInTooLong: "Prompt trop long pour le lien : il est copié, colle-le (Ctrl/Cmd + V)."')
        ->toContain('1 carte importée')
        ->toContain('{count} cartes importées');

    $en = $normalize($this->actingAs($user)->withSession(['locale' => 'en'])
        ->get('/outils/constructeur-prompts')
        ->assertOk()
        ->getContent());

    expect($en)
        ->toContain('promptCopied: "Prompt copied"')
        ->toContain('promptSaved: "Prompt saved"')
        ->toContain('saveError: "Save error. Please try again."')
        ->toContain('loadError: "Unable to load this prompt for editing."')
        ->toContain('exportError: "Unable to export the file. Please try again."')
        ->toContain('deleteError: "Error deleting. Please try again."')
        ->toContain('importError: "Error importing. Please try again."')
        ->toContain('openInGeneric: "Prompt copied: opening the conversation…"')
        ->toContain('openInGemini: "Prompt copied: paste it into Gemini (Ctrl/Cmd + V)."')
        ->toContain('openInTooLong: "Prompt too long for the link: it\'s copied, paste it (Ctrl/Cmd + V)."')
        ->toContain('1 card imported')
        ->toContain('{count} cards imported');
});

it('renders the constructeur-prompts Blade-level UI text as real localized HTML in both languages (round 30, 2026-07-27: 184 des 209 chaînes __() de cette page - tout le wizard hors i18n JS - étaient absentes des 2 fichiers lang, un visiteur anglophone voyait une page presque entièrement en français)', function () {
    Tool::where('slug', 'constructeur-prompts')->update(['is_under_construction' => false]);

    $user = User::factory()->create();

    // Échantillon représentatif réparti sur toute la page (gestion de cartes, objectif,
    // demande, ton, réglages avancés, actions de sortie, aide) - pas les 184 clés une à une,
    // mais assez pour détecter une régression systémique. Rendu via {{ __() }} Blade classique
    // (pas @json()), donc aucun souci d'échappement unicode ici - assertSee direct suffit.
    $this->actingAs($user)->withSession(['locale' => 'fr'])
        ->get('/outils/constructeur-prompts')
        ->assertOk()
        ->assertSee('Ajouter une carte', escape: false)
        ->assertSee('Diagnostic rapide', escape: false)
        ->assertSee('Votre objectif', escape: false)
        ->assertSee('Description de la demande', escape: false)
        ->assertSee('Ton de la réponse', escape: false)
        ->assertSee('Verbe d&#039;action', escape: false)
        ->assertSee('Améliorer avec mon IA', escape: false)
        ->assertSee('Comment créer un bon prompt', escape: false)
        ->assertSee('Historique', escape: false);

    $this->actingAs($user)->withSession(['locale' => 'en'])
        ->get('/outils/constructeur-prompts')
        ->assertOk()
        ->assertSee('Add a card', escape: false)
        ->assertSee('Quick check', escape: false)
        ->assertSee('Your goal', escape: false)
        ->assertSee('Prompt description', escape: false)
        ->assertSee('Response tone', escape: false)
        ->assertSee('Action verb', escape: false)
        ->assertSee('Improve with my AI', escape: false)
        ->assertSee('How to create a good prompt', escape: false)
        ->assertSee('History', escape: false);
});

it('hides the "Mes prompts" menu link from a non-admin while the tool is under construction (passe adversariale round 4, 2026-07-26)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('user.dashboard'));

    $response->assertOk();
    $response->assertDontSee('Mes prompts', escape: false);
});

it('shows the "Mes prompts" menu link to the superadmin while the tool is under construction', function () {
    $user = User::factory()->create(['email' => config('app.superadmin_email')]);
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->get(route('user.dashboard'));

    $response->assertOk();
    $response->assertSee('Mes prompts', escape: false);
});

it('hides saved prompt names/previews from /user/saved for a non-admin while the tool is under construction (passe adversariale round 11, 2026-07-27)', function () {
    $user = User::factory()->create();

    \Modules\Tools\Models\SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Nom de prompt secret',
        'prompt_text' => 'Texte de prompt qui ne devrait pas fuiter',
    ]);

    $response = $this->actingAs($user)->get(route('user.saved'));

    $response->assertOk();
    $response->assertDontSee('Nom de prompt secret', escape: false);
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

it('excludes saved prompts from /user/data-export for a non-admin while the tool is under construction (passe adversariale round 12, 2026-07-27)', function () {
    $user = User::factory()->create();

    \Modules\Tools\Models\SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt export secret',
        'prompt_text' => 'Texte complet qui ne devrait pas fuiter via l\'export RGPD',
    ]);

    $response = $this->actingAs($user)->get(route('user.data-export'));

    $response->assertOk();
    $response->assertJsonMissingPath('saved_prompts');
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
