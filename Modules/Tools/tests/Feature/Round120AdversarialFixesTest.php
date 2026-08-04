<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 120 (2026-07-30) : passe adversariale fraîche après le round 119. 1 manque réel corrigé.
//
// Le champ d'édition inline des tags (« Modifier les tags », /user/prompts) n'appliquait AUCUNE
// limite de longueur individuelle côté client : ni maxlength, ni contrôle dans saveTags(), qui
// dédoublonnait et plafonnait à 5 tags mais ignorait complètement la longueur de chacun. Or le
// label du champ promet explicitement « max 5 tags de 30 caractères max chacun », et le serveur
// applique 'tags.*' => 'string|max:30'.
//
// Conséquence réelle : dès qu'UN seul tag dépassait 30 caractères, $request->validate() levait
// une ValidationException et RIEN n'était mis à jour - pas même les tags valides du même lot.
// L'utilisateur perdait sa saisie entière pour un seul tag trop long.
//
// Note de rigueur : le rapport initial du round affirmait aussi que le message d'erreur affiché
// était le libellé technique brut « tags.1 ». VÉRIFIÉ EMPIRIQUEMENT et FAUX - lang/fr/validation.php
// mappe bien 'tags.*' => 'étiquette' (ligne 221), et Laravel résout le caractère générique : le
// message réel est « Le texte étiquette ne doit pas contenir plus de 30 caractères. » Le vrai
// défaut restant est qu'il ne désigne pas LEQUEL des tags saisis est fautif, le champ étant un
// unique input séparé par des virgules.
//
// Correctif : garde client dans saveTags() qui bloque l'envoi, nomme les tags fautifs (tronqués à
// 20 caractères pour l'affichage) et laisse la saisie intacte. La validation serveur est
// conservée telle quelle en défense en profondeur.

it('enforces the per-tag length client-side before sending (round 120)', function () {
    // Tâche #1416 (2026-08-02) : saveTags() vit désormais dans le fichier extrait
    // public/assets/tools/user-prompts/user-prompts-core.js (Alpine.data), plus dans le Blade.
    $js = file_get_contents(public_path('assets/tools/user-prompts/user-prompts-core.js'));

    expect($js)->toContain('var MAX_TAG_LENGTH = 30;');
    expect($js)->toContain('return tag.length > MAX_TAG_LENGTH;');
    // La garde doit précéder l'appel réseau DE CETTE MÉTHODE : on ne part pas chercher un 422
    // évitable. L'ancrage part de saveTags() car 3 autres méthodes de la page appellent le même
    // endpoint /api/prompts/{id} - un strpos global pointerait sur le premier d'entre eux.
    $posMethod = strpos($js, 'async saveTags(publicId, tagsInput) {');
    expect($posMethod)->not->toBeFalse();

    $posGuard = strpos($js, 'if (tagsTooLong.length > 0)', $posMethod);
    $posFetch = strpos($js, "await fetch('/api/prompts/' + publicId", $posMethod);
    expect($posGuard)->not->toBeFalse();
    expect($posFetch)->not->toBeFalse();
    expect($posGuard)->toBeLessThan($posFetch);
});

it('names the offending tags instead of a generic message (round 120)', function () {
    // Tâche #1416 (2026-08-02) : saveTags() vit désormais dans le fichier extrait
    // public/assets/tools/user-prompts/user-prompts-core.js (Alpine.data), plus dans le Blade.
    $js = file_get_contents(public_path('assets/tools/user-prompts/user-prompts-core.js'));
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    expect($js)->toContain("msgTooLong.replace(':tags', invalidTags.join(', '))");
    // Tronqué pour l'affichage : un tag de 200 caractères ne doit pas noyer le toast.
    expect($js)->toContain("tag.length > 20 ? tag.slice(0, 20) + '…' : tag");
    expect($en)->toHaveKey("Ces étiquettes dépassent 30 caractères : :tags. Raccourcissez-les avant d'enregistrer.");
});

it('keeps the existing dedupe and 5-tag cap untouched (round 120 non-regression)', function () {
    // Tâche #1416 (2026-08-02) : saveTags() vit désormais dans le fichier extrait
    // public/assets/tools/user-prompts/user-prompts-core.js (Alpine.data), plus dans le Blade.
    $js = file_get_contents(public_path('assets/tools/user-prompts/user-prompts-core.js'));

    expect($js)->toContain('if (seen[key]) return false;');
    expect($js)->toContain('.slice(0, 5);');
});

it('keeps the server-side rule as defence in depth (round 120)', function () {
    $controller = file_get_contents(base_path('Modules/Tools/app/Http/Controllers/SavedPromptController.php'));

    // Le correctif client ne remplace PAS la validation serveur : un client modifié ou une
    // requête API directe doit toujours être rejetée.
    expect($controller)->toContain("'tags.*' => 'string|max:30'");
    expect($controller)->toContain("'tags' => 'sometimes|nullable|array|max:5'");
});

it('has the wildcard attribute translated so the message stays intelligible (round 120)', function () {
    $fr = file_get_contents(base_path('lang/fr/validation.php'));

    // Vérifie le fait qui a INFIRMÉ la partie erronée du rapport : sans cette clé, le message
    // afficherait le libellé technique brut.
    expect($fr)->toContain("'tags.*' => 'étiquette'");
});

it('renders /user/prompts correctly after the round 120 fix (real page, no regression)', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt test round 120',
        'prompt_text' => 'Contenu de test',
        'tags' => ['test-round-120'],
    ]);

    $this->actingAs($user)->get('/user/prompts')->assertOk();
});
