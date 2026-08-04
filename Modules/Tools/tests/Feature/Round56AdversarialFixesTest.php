<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;
use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 56 (2026-07-27) : passe adversariale fraîche sur constructeur-prompts, 1 fix réel.
//
// duplicatePrompt() (page « Mes prompts ») n'avait AUCUNE garde anti double-invocation,
// contrairement au pattern déjà établi ailleurs dans ce périmètre (round 45-47 : draft/snapshot ;
// round 53 : dataTransfer comme source unique). Le menu ⋮ se referme au clic sans désactiver le
// déclencheur, donc rien n'empêchait de rouvrir le menu et de recliquer « Dupliquer » avant la
// fin du setTimeout(700ms) précédant le reload. Chaque clic déclenchait un POST /api/prompts/
// {id}/duplicate indépendant, et SavedPromptController::duplicate() n'a aucune contrainte
// d'unicité - N clics rapides = N copies réelles en base, chacune confirmée par son propre toast
// succès (aucun avertissement d'action redondante). Fix : Set _duplicatingIds vérifié en tête de
// fonction, id ajouté avant le fetch, retiré uniquement sur échec (succès -> reload imminent).

function makeRound56PromptTool(): void
{
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);
}

it('guards duplicatePrompt() against concurrent double-invocation via a _duplicatingIds set (round 56)', function () {
    makeRound56PromptTool();
    $user = User::factory()->create();
    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt round 56',
        'prompt_text' => 'Texte',
        'public_id' => 'r56dup123',
    ]);

    $this->actingAs($user)->get('/user/prompts')->assertOk();

    // Tâche #1416 (2026-08-02) : cette logique vit désormais dans le fichier extrait
    // public/assets/tools/user-prompts/user-prompts-core.js (Alpine.data), plus dans le HTML rendu.
    $js = file_get_contents(public_path('assets/tools/user-prompts/user-prompts-core.js'));

    // _duplicatingIds doit être déclaré comme état du composant.
    $stateDeclPos = strpos($js, '_duplicatingIds: new Set()');
    expect($stateDeclPos)->not->toBeFalse();

    // La fonction duplicatePrompt() doit vérifier le Set AVANT tout appel réseau, puis y ajouter
    // l'id, dans cet ordre exact (guard -> add -> fetch).
    $fnPos = strpos($js, 'async duplicatePrompt(publicId)');
    expect($fnPos)->not->toBeFalse();

    $guardPos = strpos($js, 'if (this._duplicatingIds.has(publicId)) return;', $fnPos);
    $addPos = strpos($js, 'this._duplicatingIds.add(publicId);', $fnPos);
    $fetchPos = strpos($js, "fetch('/api/prompts/' + publicId + '/duplicate'", $fnPos);

    expect($guardPos)->not->toBeFalse();
    expect($addPos)->not->toBeFalse();
    expect($fetchPos)->not->toBeFalse();
    expect($addPos)->toBeGreaterThan($guardPos);
    expect($fetchPos)->toBeGreaterThan($addPos);

    // En cas d'échec (réseau ou statut non-201), l'id doit être retiré du Set pour permettre
    // une nouvelle tentative légitime de l'utilisateur.
    $deleteCount = substr_count(
        substr($js, $fnPos, (strpos($js, 'async deletePrompt(publicId)', $fnPos) ?: strlen($js)) - $fnPos),
        'this._duplicatingIds.delete(publicId);'
    );
    expect($deleteCount)->toBe(2); // 1x branche "else" (statut != 201), 1x branche catch (erreur réseau)
});
