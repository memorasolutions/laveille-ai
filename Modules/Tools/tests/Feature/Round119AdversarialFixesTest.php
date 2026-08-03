<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 119 (2026-07-27) : passe adversariale fraîche après le round 118. 1 manque réel de
// gravité haute corrigé, sur le même axe que la saga 109-115 mais sur un champ jamais couvert.
//
// Le textarea « Gabarit de requête » des cartes de démarrage personnalisées
// (constructeur-prompts.blade.php, x-model="c.query_template") n'avait AUCUN attribut id. Le
// garde-fou anti-PII (prompt-anon-panel.js) résolvant tous ses champs par getElementById sur une
// liste STATIQUE de 5 ids, il lui était structurellement impossible de le surveiller - ni à la
// frappe, ni au blur.
//
// Or ce contenu n'est pas anodin : commitCardPanelBlur() appelle persistCustomCards() dès le
// blur, ce qui POST le texte tel quel vers /api/tool-preferences (sanitizeCustomCards ne fait que
// tronquer à 500 caractères, aucune détection PII). Le courriel ou le téléphone saisi est donc
// persisté en clair dans users.tool_preferences, et l'interface elle-même annonce que « ce texte
// pré-remplira automatiquement votre demande » : c'est une source de PII RÉUTILISÉE à répétition,
// jamais scannée, alors que le même texte tapé dans le champ « Tâche » juste à côté aurait
// immédiatement déclenché le bandeau d'avertissement.
//
// C'est exactement la classe de manque corrigée au round 112 pour le panneau « Mon profil » :
// la SOURCE de la fuite n'était pas surveillée, seulement les champs en aval.
//
// Correctif : id dynamique `cpCardTemplate-<cardId>` sur le textarea (même convention que
// cpCardTitleInput-<id>), et côté JS une écoute DÉLÉGUÉE sur document - indispensable car ces
// textareas sont montés/démontés par Alpine (x-if dans x-for), donc absents au chargement.
// Le blur étant un événement qui ne bulle PAS, l'écoute déléguée est posée en phase de CAPTURE.
//
// La preuve comportementale (le bandeau se déclenche réellement) est dans le test JS dédié
// tests/js/constructeur-prompts-cardtemplate-pii-guard.test.cjs, qui échoue bien contre
// l'ancien code (4 assertions sur 6) et passe contre le corrigé.

it('gives the card template textarea a resolvable dynamic id (round 119)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain('<textarea :id="\'cpCardTemplate-\' + c.id"');
    expect($blade)->toContain('x-model="c.query_template"');
});

it('watches the card template through delegated listeners, not the static list (round 119)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/prompt-anon-panel.js'));

    expect($js)->toContain("const CARD_TEMPLATE_PREFIX = 'cpCardTemplate-';");
    expect($js)->toContain('const isCardTemplateField =');
    expect($js)->toContain('const isWatched =');
    // Écoute déléguée sur document : les 5 champs fixes gardent leur écoute directe, mais les
    // gabarits de cartes n'existent pas au chargement.
    expect($js)->toContain("document.addEventListener('input', function (e) {");
    // Le 3e argument true (capture) est OBLIGATOIRE : 'blur' ne remonte pas dans l'arbre.
    expect($js)->toContain('}, true);');
});

it('keeps the static five-field wiring intact (round 119 non-regression)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/prompt-anon-panel.js'));

    // Round 125 (2026-07-30) : cette assertion figeait le littéral EXACT à cinq champs. C'était
    // trop strict : elle exprimait « la liste ne doit pas changer » alors que l'intention réelle du
    // round 119 était « les cinq champs statiques restent branchés ». Ajouter une 6e source de
    // fuite légitime (cpExamples) la faisait échouer alors que la protection était RENFORCÉE.
    // On vérifie donc la présence de chacun des cinq, sans interdire l'extension de la liste.
    $pos = strpos($js, 'const watchedFields = [');
    expect($pos)->not->toBeFalse();

    $declaration = substr($js, $pos, (int) strpos($js, ';', $pos) - $pos);

    foreach (['taskField', 'personaCustomField', 'audienceCustomField', 'verbCustomField', 'constraintCustomField'] as $field) {
        expect($declaration)->toContain($field);
    }

    expect($declaration)->toContain('.filter(Boolean)');
    expect($js)->toContain('watchedFields.forEach(function (field) {');
});

it('uses the membership helper everywhere instead of raw indexOf (round 119)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/prompt-anon-panel.js'));

    // Les 2 tests d'appartenance (bouton fermer + « Masquer mes infos ») doivent accepter les
    // gabarits de cartes, sinon le bandeau s'afficherait sans pouvoir être ni fermé ni corrigé.
    expect($js)->toContain('isWatched(currentField)');
    expect($js)->toContain('!isWatched(field)');
    expect($js)->not->toContain('watchedFields.indexOf(currentField) !== -1');
    expect($js)->not->toContain('watchedFields.indexOf(field) === -1');
});

it('labels the card template field with a translatable name (round 119)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/prompt-anon-panel.js'));
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    expect($js)->toContain('const labelFor =');
    expect($js)->toContain('i18n.anonFieldCardTemplate ||');
    expect($blade)->toContain('anonFieldCardTemplate:');
    expect($en)->toHaveKey('Gabarit de requête de cette carte');
});

it('renders the constructeur-prompts page correctly after the round 119 fix (real page, no regression)', function () {
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
