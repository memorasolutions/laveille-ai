<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 109 (2026-07-27) : passe adversariale fraîche après le lot round 108 (focus WCAG de la
// modale de confirmation partagée). 1 manque réel corrigé, un vrai trou de confidentialité :
//
// Le garde-fou anti-données-personnelles (bandeau "On dirait qu'il y a des infos
// personnelles..." + AnonymizerCore.detectEntities, public/assets/tools/constructeur-prompts/
// prompt-anon-panel.js) ne surveillait QUE le champ #cpTaskObject. Or 4 autres champs de texte
// libre du wizard alimentent aussi le prompt final copié/partagé (get personaText/audienceText/
// prompt dans constructeur-prompts-core.js) sans jamais être scannés : #cpPersonaCustom (Rôle
// personnalisé), #cpAudienceCustom (Audience personnalisée), #cpVerbCustom (Verbe personnalisé),
// #cpConstraintCustom (Contraintes personnalisées) - dont le placeholder invite justement à des
// descriptions narratives libres ("Ex: un expert en cybersécurité spécialisé en PME
// québécoises"). Un utilisateur pouvait y saisir un nom/courriel/téléphone réel sans jamais
// recevoir l'avertissement, puis copier/partager ce texte tel quel.
//
// Fixé : les 4 IDs manquants ajoutés au Blade ; le garde-fou étend sa surveillance aux 5 champs
// (checkEntities(field) scanne UN champ à la fois, pas une concaténation - pour identifier
// précisément où est la fuite) ; un seul bandeau DOM partagé, repositionné dynamiquement et
// dont le texte nomme le champ concerné ; le bouton "Masquer mes infos" cible désormais le
// champ qui a déclenché l'alerte (activeField) au lieu de toujours écrire dans #cpTaskObject -
// sinon le texte original avec PII resterait intact dans son champ d'origine ET une copie
// masquée serait dupliquée ailleurs (fuite non corrigée, juste dupliquée).

it('extends the anti-PII guard to the 4 custom fields, not just the task field (round 109)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/prompt-anon-panel.js'));

    expect($js)->toContain("document.getElementById('cpPersonaCustom')");
    expect($js)->toContain("document.getElementById('cpAudienceCustom')");
    expect($js)->toContain("document.getElementById('cpVerbCustom')");
    expect($js)->toContain("document.getElementById('cpConstraintCustom')");
    expect($js)->toContain('function checkEntities(field)');
    expect($js)->toContain('let activeField = null;');
    // Round 109 : le bug warnBanner.parentNode.nextElementSibling (pointe sur un frère du
    // CONTENEUR, pas sur le champ lui-même) ne doit jamais réapparaître.
    expect($js)->not->toContain('warnBanner.parentNode.nextElementSibling');
    expect($js)->toContain('warnBanner.nextElementSibling');
});

it('adds the id attributes to the 4 custom fields in the blade template (round 109)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain('id="cpPersonaCustom"');
    expect($blade)->toContain('id="cpAudienceCustom"');
    expect($blade)->toContain('id="cpVerbCustom"');
    expect($blade)->toContain('id="cpConstraintCustom"');
});

it('renders the constructeur-prompts page correctly after the round 109 fix (real page, no regression)', function () {
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
