<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Étape 8 (section 8) de .outils/PLAN-CONSTRUCTEUR-PROMPTS-ULTRA-2026-08-02.md : suite de tests
// Pest neuve pour la page réécrite (étape 4, « page blanche »). Couvre uniquement ce qui est
// vérifiable côté serveur (rendu HTML) - le comportement Alpine.js en navigateur viendra à
// l'étape 10 via Playwright. L'outil reste gaté superadmin-only pendant la révision
// (is_under_construction = true, comportement confirmé via EnsureToolNotUnderConstruction /
// Tool::isAccessibleTo() - voir Modules/Tools/tests/Feature/ConstructeurPromptsGateTest.php) :
// un visiteur non-admin reçoit la vue placeholder "under-construction", jamais la vraie page.

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

// Closure locale (pas une fonction globale) : évite tout risque de collision de nom avec un
// autre fichier de test Pest chargé dans le même process.
$superAdmin = function (): User {
    $user = User::factory()->create(['email' => config('app.superadmin_email')]);
    $user->assignRole('super_admin');

    return $user;
};

// Isole le HTML propre à l'outil (du x-data="promptBuilder(...)" jusqu'au script prompt-verifier-
// rules.js inclus) pour ne pas faux-positiver sur le HTML global du site (header/nav authentifié,
// scripts partagés type wow.js) qui entoure la page - trouvé en debug round 1 de cette suite :
// substr_count('type="radio"') comptait aussi l'occurrence dans le commentaire Blade explicatif,
// "Mes prompts" apparaît légitimement dans le menu "Mon espace" du header sur TOUTE page
// authentifiée (lien vers /user/prompts, fonctionnalité toujours active, hors scope de cette
// page), et "undefined" apparaît dans un script sitewide (wow.js) sans rapport avec @js().
$toolSection = function (string $html): string {
    $start = strpos($html, 'x-data="promptBuilder(');
    $end = strpos($html, 'assets/tools/constructeur-prompts/prompt-verifier-rules.js');
    expect($start)->not->toBeFalse();
    expect($end)->not->toBeFalse();

    return substr($html, $start, $end - $start);
};

it('renders the rewritten page with a 200 for an authenticated superadmin (l\'outil reste gaté is_under_construction, seul un superadmin voit la vraie page)', function () use ($superAdmin) {
    $user = $superAdmin();

    $response = $this->actingAs($user)->get('/outils/constructeur-prompts');

    $response->assertOk();
    $response->assertDontSee('id="uc-title"', escape: false);
});

it('shows the under-construction placeholder to a non-superadmin authenticated user, confirming the gate still protects the rewritten page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/outils/constructeur-prompts');

    $response->assertOk();
    $response->assertSee('id="uc-title"', escape: false);
});

it('renders the 9 card titles in the served HTML', function () use ($superAdmin) {
    $user = $superAdmin();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)
        ->toContain('Rédiger')
        ->toContain('Résumer un document')
        ->toContain('Corriger et améliorer un texte')
        ->toContain('Analyser ou comparer')
        ->toContain('Expliquer simplement')
        ->toContain('Trouver des idées')
        ->toContain('Préparer une activité ou un questionnaire')
        ->toContain('Traduire')
        ->toContain('Autre chose');
});

it('renders an accessible fieldset/legend structure with exactly 9 native cpCard radio inputs', function () use ($superAdmin) {
    $user = $superAdmin();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain('<fieldset class="cp-cards"');
    expect($html)->toContain('<legend>');
    // name="cpCard" est l'ancre fiable (9 exactement) : un commentaire Blade explicatif juste
    // au-dessus du fieldset mentionne aussi littéralement "<input type=\"radio\">" à titre
    // d'exemple dans sa prose, ce qui fausserait un comptage brut de "type=\"radio\"" (10 au lieu
    // de 9) - trouvé en debug round 1 de cette suite.
    expect(substr_count($html, 'name="cpCard"'))->toBe(9);
});

it('never mentions the retired out-of-scope features inside the tool itself: prompt library tab, strict framing, AI-assisted meta-prompt, icon picker', function () use ($superAdmin, $toolSection) {
    $user = $superAdmin();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();
    $tool = $toolSection($html);

    // "Mes prompts" est volontairement EXCLU de cette liste : ce texte apparaît légitimement
    // dans le menu "Mon espace" du header sur toute page authentifiée (lien vers /user/prompts,
    // fonctionnalité de bibliothèque toujours active, distincte de l'ancien onglet intégré à
    // même cette page qui, lui, a été retiré). On vérifie ici seulement l'intérieur de l'outil.
    expect($tool)->not->toContain('cadre strict');
    expect($tool)->not->toContain('Améliorer avec mon IA');
    expect($tool)->not->toContain('icon-picker');
    expect($tool)->not->toContain('sélecteur d\'icônes');
});

it('shows a visible button pointing to the anonymizer tool via the tools.show route', function () use ($superAdmin) {
    $user = $superAdmin();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain('Masquer mes informations personnelles');
    expect($html)->toContain(route('tools.show', ['slug' => 'anonymiseur']));
});

it('includes both required JS files: constructeur-prompts-core.js and prompt-verifier-rules.js', function () use ($superAdmin) {
    $user = $superAdmin();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain('assets/tools/constructeur-prompts/constructeur-prompts-core.js');
    expect($html)->toContain('assets/tools/constructeur-prompts/prompt-verifier-rules.js');
});

it('shows the warning about data sent to a service hosted abroad', function () use ($superAdmin) {
    $user = $superAdmin();

    $response = $this->actingAs($user)->get('/outils/constructeur-prompts');

    $response->assertOk();
    // assertSee() échappe la chaîne cherchée de la même façon que Blade échappe l'apostrophe de
    // "l'étranger" ({{ }} -> ENT_QUOTES -> l&#039;étranger) : comparaison robuste peu importe la
    // forme d'échappement HTML réellement produite.
    $response->assertSee("Votre prompt sera envoyé à un service hébergé à l'étranger", escape: true);
});

it('never leaks a raw "undefined" or "[object Object]" string inside the tool section (sign of a broken @js() serialization)', function () use ($superAdmin, $toolSection) {
    $user = $superAdmin();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();
    $tool = $toolSection($html);

    expect($tool)->not->toContain('undefined');
    expect($tool)->not->toContain('[object Object]');
});
