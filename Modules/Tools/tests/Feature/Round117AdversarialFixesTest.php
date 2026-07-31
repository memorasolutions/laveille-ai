<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 117 (2026-07-27) : passe adversariale fraîche après le lot round 116. 1 manque réel
// corrigé, axe i18n.
//
// Le label du champ d'édition des tags sur /user/prompts
// (Modules/Tools/resources/views/user/prompts/index.blade.php ligne 232) rend
// __('Tags (séparés par des virgules, max 5 tags de 30 caractères max chacun)'). Cette chaîne
// n'existait dans AUCUN fichier de langue : le libellé Blade avait été mis à jour pour refléter
// la validation réelle du backend (SavedPromptController : tags.* => string|max:30) sans que la
// traduction suive. Pire, lang/en.json et lang/fr.json contenaient encore une entrée ORPHELINE
// de l'ancien libellé « Tags (séparés par des virgules, max 5) », prouvée morte (0 usage dans
// tout le code) - donc un traducteur aurait corrigé la mauvaise clé.
//
// Conséquence concrète : un utilisateur en anglais qui clique « Modifier les tags » sur une carte
// de prompt voyait ce label en français brut, au milieu d'une interface autrement entièrement
// anglaise. Même type d'incohérence que le round 114 avait corrigé pour le bandeau anti-PII, mais
// sur un élément de formulaire présent sur CHAQUE carte de prompt sauvegardé.
//
// Vérification d'exhaustivité effectuée avant de corriger : sur les 329 clés __() distinctes
// rendues par les 2 pages de l'outil (52 sur user/prompts, 277 sur
// public/tools/constructeur-prompts), celle-ci était la SEULE absente de en.json. Le manque est
// isolé, pas systémique - inutile de lancer un chantier i18n plus large.
//
// Correctif : clé renommée en place dans en.json (avec la traduction anglaise) et dans fr.json ;
// l'orpheline morte est retirée des deux fichiers.

it('has an English translation for the tags edit label actually rendered by the blade (round 117)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    expect($blade)->toContain("__('Tags (séparés par des virgules, max 5 tags de 30 caractères max chacun)')");
    expect($en)->toHaveKey('Tags (séparés par des virgules, max 5 tags de 30 caractères max chacun)');
    expect($en['Tags (séparés par des virgules, max 5 tags de 30 caractères max chacun)'])->toBe('Tags (comma-separated, max 5 tags of up to 30 characters each)');
});

it('removed the dead orphan translation key for the previous label (round 117)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);
    $fr = json_decode(file_get_contents(lang_path('fr.json')), true);

    expect($en)->not->toHaveKey('Tags (séparés par des virgules, max 5)');
    expect($fr)->not->toHaveKey('Tags (séparés par des virgules, max 5)');
});

it('keeps every fr.json key present in en.json (round 117 invariant)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);
    $fr = json_decode(file_get_contents(lang_path('fr.json')), true);

    $missing = array_keys(array_diff_key($fr, $en));
    expect($missing)->toBe([]);
});

it('renders /user/prompts correctly after the round 117 fix (real page, no regression)', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt test round 117',
        'prompt_text' => 'Contenu de test',
        'tags' => ['test-round-117'],
    ]);

    $this->actingAs($user)->get('/user/prompts')->assertOk();
});
