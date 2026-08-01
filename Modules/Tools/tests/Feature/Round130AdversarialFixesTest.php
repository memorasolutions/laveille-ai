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

// Round 152 (2026-08-02) : les DEUX <select> "Choisir un rôle" / "Verbe d'action" verrouillés par
// ce test ont été retirés - écran 3 remplace ces menus déroulants par de VRAIES cartes radio
// cliquables (x-tools::prompt-card, un input par option), exigence explicite de la refonte (cartes
// accessibles au clavier, jamais un <div> qui imite un bouton). Il n'existe donc plus un SEUL champ
// portant x-model="personaPreset" : cette valeur est maintenant répartie sur N boutons radio, un par
// carte. L'obligation ARIA a été reportée au bon endroit du point de vue accessibilité : le
// role="radiogroup" qui les enveloppe (attribut aria-required valide sur ce rôle selon la spec
// WAI-ARIA, et sémantiquement plus correct qu'un select unique - annonce le GROUPE comme requis,
// pas une des options). Le champ personaCustom (mode "Personnalisé"), lui, n'a pas changé de forme
// (toujours un <input> unique) : son assertion d'origine reste vérifiée telle quelle.
it('announces the persona field as required, like the other two (round 130, re-ancré round 152)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain('role="radiogroup" :aria-required="personaType === \'preset\'"');
    expect($blade)->toContain('x-model="personaCustom" :aria-required="personaType === \'custom\'"');
});

// Re-ancré round 152 (voir commentaire du test précédent) : le radiogroup "preset" n'a plus de
// x-model propre (il enveloppe N boutons radio) - la garde "jamais figé à true" porte donc sur SA
// forme actuelle (role="radiogroup" ... aria-required="true" serait le même bogue que celui que ce
// test empêchait à l'origine : annoncer comme obligatoire un groupe de cartes masqué par x-show).
it('binds it to the active mode instead of hardcoding true (round 130, re-ancré round 152)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    // Les 2 variantes sont en x-show exclusif : une valeur figée annoncerait comme obligatoire un
    // champ (ou groupe) masqué. Le liage suit exactement le patron déjà retenu pour le verbe.
    expect($blade)->not->toContain('role="radiogroup" aria-required="true"');
    expect($blade)->not->toContain('x-model="personaCustom" aria-required="true"');
});

// Re-ancré round 152 : verbType === 'preset' porte toujours :aria-required, mais depuis le
// role="radiogroup" des cartes de verbe (même report qu'expliqué pour la persona ci-dessus) plutôt
// que depuis le <select> retiré. Le compte total (5) et le contrat isValid() restent inchangés au
// caractère près - c'est la SEULE chose que ce test vérifie vraiment, la structure du dessous a le
// droit de changer.
it('keeps all three required fields consistently marked (round 130 non-regression, re-ancré round 152)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    // Le contrat que ce test verrouille : les 3 champs exigés par isValid() sont annoncés.
    expect($js)->toContain('return this.personaText.length > 0 && this.taskObject.length > 0 && hasVerb;');

    expect($blade)->toContain('id="cpTaskObject"');
    expect($blade)->toContain('aria-required="true"');
    expect($blade)->toContain('role="radiogroup" :aria-required="verbType === \'preset\'"');
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
