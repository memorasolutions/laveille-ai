<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 112 (2026-07-27) : passe adversariale fraîche après le lot round 111 (dispatch input sur
// l'auto-remplissage "Mon profil"). 1 manque réel corrigé, la RACINE de la saga PII des rounds
// 109-111 :
//
// La saga des rounds 109-111 protège les 5 champs libres DU WIZARD (constructeur-prompts.blade.php)
// contre toute assignation programmatique qui contournerait le garde-fou anti-PII. Mais le
// formulaire "Mon profil" de /user/prompts (Modules/Tools/resources/views/user/prompts/
// index.blade.php) - la SOURCE MÊME de 2 de ces 5 valeurs (personaCustom ← profile_role,
// constraintCustom ← profile_style/profile_constraints) - n'avait AUCUN garde-fou anti-PII :
// aucune référence à AnonymizerCore nulle part sur cette page. L'utilisateur pouvait taper
// directement une vraie info personnelle dans #profile_role/#profile_style/#profile_constraints
// et saveProfile() l'envoyait telle quelle en base, sans jamais la scanner - et un utilisateur
// qui n'ouvre jamais le wizard pour un NOUVEAU prompt (ex. il édite toujours son unique prompt
// existant via ?edit=ID) voyait son profil contenant potentiellement une PII jamais scannée à
// aucun moment du parcours.
//
// Fixé : nouveau fichier public/assets/tools/constructeur-prompts/profile-anon-guard.js (garde-fou
// simplifié, inspiré de prompt-anon-panel.js mais sans éditeur riche - juste un avertissement),
// chargé sur /user/prompts avec le moteur de détection seul (anonymizer-core.js, sans
// anonymizer-rich.js/anonymizer-ui.js, inutiles ici).

it('loads the anti-PII guard script and the detection engine on /user/prompts (round 112)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));

    expect($blade)->toContain("assets/tools/anonymiseur/anonymizer-core.js");
    expect($blade)->toContain("assets/tools/constructeur-prompts/profile-anon-guard.js");
    // Ne doit PAS charger l'éditeur riche complet (inutile sur cette page, pas de workflow
    // d'anonymisation ici, juste une détection + avertissement).
    expect($blade)->not->toContain('anonymizer-rich.js');
    expect($blade)->not->toContain('anonymizer-ui.js');
});

it('defines a PII guard watching the 3 profile fields without forwarding the native event to checkField (round 112)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/profile-anon-guard.js'));

    expect($js)->toContain("if (!window.AnonymizerCore)");
    expect($js)->toContain("'profile_role'");
    expect($js)->toContain("'profile_style'");
    expect($js)->toContain("'profile_constraints'");
    expect($js)->toContain('window.AnonymizerCore.detectEntities(currentValue)');
    // Le bug de la 1re conversion ES5 (l'objet Event natif transmis à checkField() au lieu du
    // fieldId, cassant silencieusement la détection sur 'input') ne doit jamais réapparaître.
    expect($js)->not->toContain('checkField.apply(null, args)');
    expect($js)->toContain('checkField(fieldId)');
});

it('renders /user/prompts correctly after the round 112 fix (real page, no regression)', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt test round 112',
        'prompt_text' => 'Contenu de test',
        'tags' => ['test-round-112'],
    ]);

    $this->actingAs($user)->get('/user/prompts')->assertOk();
});
