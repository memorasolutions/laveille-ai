<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 113 (2026-07-27) : passe adversariale fraîche après le lot round 112 (garde-fou anti-PII
// "Mon profil"). 1 manque réel corrigé, dans le fichier même censé clore la saga PII :
//
// profile-anon-guard.js (round 112) n'effectuait AUCUN scan de la valeur INITIALE des 3 champs au
// chargement de la page - setupField() attachait seulement des écouteurs 'input'/'blur' qui ne se
// déclenchent qu'après une frappe active de l'utilisateur. Or les valeurs initiales viennent
// directement de la base via x-model Alpine (Illuminate\Support\Js::from($promptProfile[...])),
// une assignation programmatique qui ne dispatch jamais d'événement 'input' natif - exactement la
// même classe de bug corrigée à 4 reprises dans le wizard (rounds 100/110/111) mais jamais fermée
// ici. Un utilisateur ayant déjà une PII enregistrée dans son profil (avant l'existence même de ce
// garde-fou) rouvrait /user/prompts sans jamais voir le bandeau tant qu'il ne retouchait pas
// activement le champ contenant déjà la PII.
//
// Fixé : appel explicite de checkField(fieldId) juste après setupField(fieldId) dans la boucle
// DOMContentLoaded, pour scanner la valeur déjà présente au chargement (pas seulement les futures
// interactions).

it('scans the initial field value on page load, not only future interactions (round 113)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/profile-anon-guard.js'));

    expect($js)->toContain('// Round 113 (2026-07-27, passe adversariale)');
    // La boucle DOMContentLoaded doit appeler setupField() ET checkField() pour chaque champ
    // (le 2e étant le fix - scanner la valeur déjà en base au chargement).
    expect(substr_count($js, 'setupField(fieldIds[i]);'))->toBe(1);
    expect(substr_count($js, 'checkField(fieldIds[i]);'))->toBe(1);
});

it('renders /user/prompts correctly after the round 113 fix (real page, no regression)', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt test round 113',
        'prompt_text' => 'Contenu de test',
        'tags' => ['test-round-113'],
    ]);

    $this->actingAs($user)->get('/user/prompts')->assertOk();
});
