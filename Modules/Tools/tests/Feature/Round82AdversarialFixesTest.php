<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 82 (2026-07-27) : passe adversariale fraîche après le lot round 81 (nom d'import
// localStorage). 3 manques réels corrigés :
//
// 1. constructeur-prompts-core.js:661 (addToHistory) - err.serverMessage n'était vrai QUE si le
//    corps JSON contenait un `message`, sans distinguer la SOURCE de ce message. Un 429 (throttle
//    tools-api, 60/min) renvoie le texte anglais fixe du framework Laravel ("Too Many Attempts."),
//    jamais traduit, injecté verbatim dans le bandeau d'erreur d'un utilisateur FR. Fixé en
//    restreignant serverMessage=true au seul statut 422 (validation applicative, seul cas
//    aujourd'hui traduit via __() côté SavedPromptController).
// 2. ToolPreferenceController.php (9 occurrences) - messages ValidationException::withMessages()
//    jamais enveloppés de __(), incohérent avec SavedPromptController qui traduit déjà son propre
//    message custom. Impact UI actuel nul (aucun flux ne les affiche bruts aujourd'hui) mais gap
//    réel dans la réponse API elle-même. Fixé : les 9 messages passés par __() + clés lang/en.json.
// 3. index.blade.php:157/323 + constructeur-prompts.blade.php:113 - #9CA3AF (icône favori non
//    cochée + bordure "Ajouter une carte") = contraste ≈2,54:1 sur blanc, sous le seuil WCAG 3:1
//    (SC 1.4.11, composants non-textuels) et très sous l'AAA 7:1 de la charte du projet. Remplacé
//    par var(--c-text-muted, #52586A) = 7,09:1 (AAA), déjà utilisé site-wide pour ce rôle.

it('the JS file only trusts server error messages on HTTP 422 (round 82)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain("err.serverMessage = r.status === 422 && !!(body && body.message);");
});

it('has English translations for the 9 ToolPreferenceController validation messages (round 82)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $expected = [
        'Trop volumineux.' => 'Too large.',
        'Format de couleurs invalide.' => 'Invalid colors format.',
        'Format de durées invalide.' => 'Invalid durations format.',
        'Format de seuils invalide.' => 'Invalid thresholds format.',
        'Seuils invalides (jaune doit être inférieur à vert).' => 'Invalid thresholds (yellow must be lower than green).',
        'Couleur invalide.' => 'Invalid color.',
        'Format de profil invalide.' => 'Invalid profile format.',
        'Chaque champ du profil doit être une chaîne de caractères.' => 'Each profile field must be a string.',
        'Format de cartes invalide.' => 'Invalid cards format.',
    ];

    foreach ($expected as $fr => $translated) {
        expect($en)->toHaveKey($fr);
        expect($en[$fr])->toBe($translated);
    }
});

it('ToolPreferenceController wraps all 9 validation messages in __() (round 82)', function () {
    $code = file_get_contents(app_path('../Modules/Tools/app/Http/Controllers/ToolPreferenceController.php'));

    $messages = [
        'Trop volumineux.',
        'Format de couleurs invalide.',
        'Format de durées invalide.',
        'Format de seuils invalide.',
        'Seuils invalides (jaune doit être inférieur à vert).',
        'Couleur invalide.',
        'Format de profil invalide.',
        'Chaque champ du profil doit être une chaîne de caractères.',
        'Format de cartes invalide.',
    ];

    foreach ($messages as $msg) {
        expect($code)->toContain("__('{$msg}')");
    }
});

it('returns the translated profile-format message on EN locale via the real API (round 82)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['locale' => 'en'])
        ->postJson('/api/tool-preferences/constructeur-prompts', [
            'key' => 'prompt_profile',
            'value' => 'not-an-array-or-object',
        ]);

    $response->assertStatus(422);
    expect($response->json('errors.value.0'))->toBe('Invalid profile format.');
});

it('does not use the low-contrast #9CA3AF color for the favorite icon or add-card border anymore (round 82)', function () {
    $userPromptsIndex = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));
    $constructeurBlade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($userPromptsIndex)->not->toContain("'#9CA3AF'");
    expect($userPromptsIndex)->toContain('var(--c-text-muted, #52586A)');
    expect($constructeurBlade)->toContain('border:2px dashed var(--c-text-muted, #52586A)');
});
