<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 131 (2026-07-30) : la seule explication du blocage n'était jamais annoncée.
//
// Trois boutons sont désactivés tant que !isValid : Copier le prompt, Améliorer avec mon IA,
// Exporter .txt. La seule chose qui dit POURQUOI est la bannière « Choisissez un objectif... ».
// Elle n'avait ni role ni aria-live, alors que les 4 autres alertes du même fichier en ont
// (saveError assertive, taskNotice polite, carte manquante assertive, plafond de cartes polite).
//
// Le mécanisme qu'on pourrait croire compensatoire ne l'est pas : le panneau « Diagnostic rapide »,
// qui liste précisément ce qui manque et porte bien un aria-live, est en `x-show="isValid"`. Il est
// donc caché EXACTEMENT dans le cas où l'utilisateur en aurait besoin. Et l'aria-required du round
// 130 ne s'annonce qu'au focus du champ concerné : il ne remplace pas une explication globale qui
// apparaît ailleurs dans le DOM pendant la frappe.
//
// Conséquence : un utilisateur de lecteur d'écran voit trois boutons inertes sans jamais entendre
// pourquoi, et doit explorer la page à l'aveugle pour trouver la bannière.
//
// Correctif en 2 volets :
//   1. role="status" + aria-live="polite" sur la bannière. « polite » et non « assertive » car elle
//      bascule au fil de la frappe : une annonce assertive interromprait à chaque changement d'état.
//   2. aria-describedby depuis les 3 boutons gatés vers cette bannière, pour que la raison soit
//      donnée AU MOMENT où l'utilisateur atteint le bouton, sans dépendre de sa découverte
//      fortuite. Quand le formulaire devient valide, la bannière passe en display:none et la
//      description est ignorée par les technologies d'assistance : aucun bruit résiduel.

it('announces the validity hint instead of leaving it silent (round 131)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain('<div x-show="!isValid" id="cpValidityHint" role="status" aria-live="polite"');
});

it('uses polite rather than assertive on a hint that toggles while typing (round 131)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->not->toContain('id="cpValidityHint" role="alert"');
    expect($blade)->not->toContain('id="cpValidityHint" role="status" aria-live="assertive"');
});

it('links all three gated buttons to the reason they are disabled (round 131)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain('@click="copy()" :disabled="!isValid" aria-describedby="cpValidityHint"');
    expect($blade)->toContain('@click="toggleMetaPrompt()" :disabled="!isValid" aria-describedby="cpValidityHint"');
    expect($blade)->toContain('@click="exportPrompt()" :disabled="!isValid" aria-describedby="cpValidityHint"');

    // 1 sur la bannière (id) + 3 références.
    expect(substr_count($blade, 'cpValidityHint'))->toBe(4);
});

it('confirms the diagnostic panel cannot serve as the fallback (round 131 root cause)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    // C'est CE x-show qui rend le panneau détaillé inutilisable comme explication du blocage :
    // il n'apparaît qu'une fois le formulaire déjà valide. Si ce contrat change un jour, le
    // correctif du round 131 mérite d'être réévalué. Markup mis à jour au correctif #3/#5
    // (2026-08-05, disclosure « Vérifications » ex-« Diagnostic rapide ») : même x-show="isValid"
    // x-cloak, désormais sur le wrapper .ct-disclosure au lieu du style inline d'origine.
    expect($blade)->toContain('<div class="ct-disclosure" x-show="isValid" x-cloak>');
});

it('keeps the sibling alerts untouched (round 131 non-regression)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain('x-text="saveError" role="alert" aria-live="assertive"');
    expect($blade)->toContain('x-text="taskNotice" role="status" aria-live="polite"');
});

it('renders the wizard after the round 131 fix (real page)', function () {
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
