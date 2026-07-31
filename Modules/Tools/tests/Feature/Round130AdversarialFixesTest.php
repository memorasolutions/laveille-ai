<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 130 (2026-07-30) : incohérence ARIA sur un champ obligatoire.
//
// isValid() (constructeur-prompts-core.js) exige TROIS champs :
//     return this.personaText.length > 0 && this.taskObject.length > 0 && hasVerb;
// Les trois portent un astérisque rouge dans leur libellé de section. Mais seuls taskObject
// (aria-required="true") et le verbe (:aria-required lié au mode preset/custom) l'annonçaient
// réellement aux technologies d'assistance. Les deux champs du rôle de l'IA - le select des rôles
// prédéfinis et l'input du rôle personnalisé - n'avaient AUCUN attribut.
//
// L'astérisque vit dans le libellé du BOUTON DE SECTION parent, pas sur le champ : un utilisateur
// de lecteur d'écran qui tabule directement jusqu'au select n'a donc aucun signal d'obligation.
//
// Aucun mécanisme compensatoire : le panneau « Diagnostic rapide » est en x-show="isValid", donc
// invisible précisément quand le rôle manque ; et l'alerte générique x-show="!isValid" ne porte ni
// role="alert" ni aria-live, et ne nomme jamais le champ fautif.
//
// Ce n'était pas un vestige d'avant-refonte : les 4 champs (persona et verbe) datent du même
// commit. Le traitement a divergé À L'INTÉRIEUR de la refonte, ce qui rend l'écart d'autant plus
// facile à manquer - le patron correct était juste 20 lignes plus bas dans le même fichier.
//
// Correctif : miroir exact du patron du verbe, lié au mode actif pour ne jamais annoncer comme
// obligatoire un champ que l'utilisateur ne voit pas (les deux variantes sont en x-show exclusif).

it('announces the persona field as required, like the other two (round 130)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain('x-model="personaPreset" :aria-required="personaType === \'preset\'"');
    expect($blade)->toContain('x-model="personaCustom" :aria-required="personaType === \'custom\'"');
});

it('binds it to the active mode instead of hardcoding true (round 130)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    // Les 2 variantes sont en x-show exclusif : une valeur figée annoncerait comme obligatoire un
    // champ masqué. Le liage suit exactement le patron déjà retenu pour le verbe.
    expect($blade)->not->toContain('x-model="personaPreset" aria-required="true"');
    expect($blade)->not->toContain('x-model="personaCustom" aria-required="true"');
});

it('keeps all three required fields consistently marked (round 130 non-regression)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    // Le contrat que ce test verrouille : les 3 champs exigés par isValid() sont annoncés.
    expect($js)->toContain('return this.personaText.length > 0 && this.taskObject.length > 0 && hasVerb;');

    expect($blade)->toContain('id="cpTaskObject"');
    expect($blade)->toContain('aria-required="true"');
    expect($blade)->toContain(':aria-required="verbType === \'preset\'"');
    expect($blade)->toContain(':aria-required="verbType === \'custom\'"');

    // 5 au total : tâche (1) + verbe (2) + rôle (2).
    expect(substr_count($blade, 'aria-required'))->toBe(5);
});

it('renders the wizard after the round 130 fix (real page)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk();
});
