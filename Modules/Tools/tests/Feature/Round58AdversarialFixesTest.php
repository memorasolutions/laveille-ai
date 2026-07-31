<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;
use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 58 (2026-07-27) : passe adversariale fraîche sur constructeur-prompts, 1 fix réel.
//
// deletePrompt() (page « Mes prompts ») n'avait AUCUNE garde anti double-invocation, alors que
// duplicatePrompt() (même fichier, round 56) en avait une. La modale de confirmation globale
// (confirm-modal.blade.php) ne désactive pas synchroniquement son bouton « Confirmer » avant de
// se fermer (x-show/x-transition seulement) : un double-clic déclenche deux DELETE quasi
// simultanés. Le 1er réussit (204, SavedPromptController::destroy() supprime la ligne). Le 2e
// tombe sur firstOrFail() déjà supprimé côté serveur -> ModelNotFoundException -> 404 -> le
// front affiche "Erreur lors de la suppression." alors que la suppression a pleinement réussi -
// un message trompeur, exactement le symptôme que round 56 avait corrigé pour duplicatePrompt()
// dans ce même fichier. Fix : Set _deletingIds vérifié en tête de fonction (guard-then-add),
// retiré du Set uniquement en cas d'échec.

function makeRound58PromptTool(): void
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

it('guards deletePrompt() against concurrent double-invocation via a _deletingIds set (round 58)', function () {
    makeRound58PromptTool();
    $user = User::factory()->create();
    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt round 58',
        'prompt_text' => 'Texte',
        'public_id' => 'r58del123',
    ]);

    $html = $this->actingAs($user)->get('/user/prompts')->assertOk()->getContent();

    // _deletingIds doit être déclaré comme état du composant.
    $stateDeclPos = strpos($html, '_deletingIds: new Set()');
    expect($stateDeclPos)->not->toBeFalse();

    // La fonction deletePrompt() doit vérifier le Set AVANT tout appel réseau, puis y ajouter
    // l'id, dans cet ordre exact (guard -> add -> fetch).
    $fnPos = strpos($html, 'async deletePrompt(publicId)');
    expect($fnPos)->not->toBeFalse();

    $guardPos = strpos($html, 'if (this._deletingIds.has(publicId)) return;', $fnPos);
    $addPos = strpos($html, 'this._deletingIds.add(publicId);', $fnPos);
    $fetchPos = strpos($html, "fetch('/api/prompts/' + publicId, {", $fnPos);

    expect($guardPos)->not->toBeFalse();
    expect($addPos)->not->toBeFalse();
    expect($fetchPos)->not->toBeFalse();
    expect($addPos)->toBeGreaterThan($guardPos);
    expect($fetchPos)->toBeGreaterThan($addPos);

    // En cas d'échec (réseau ou statut non-204), l'id doit être retiré du Set pour permettre
    // une nouvelle tentative légitime de l'utilisateur.
    $fnEndPos = strpos($html, '_reloadIfListEmpty() {') ?: strlen($html);
    $deleteCount = substr_count(substr($html, $fnPos, $fnEndPos - $fnPos), 'this._deletingIds.delete(publicId);');
    expect($deleteCount)->toBe(2); // 1x branche "else" (statut != 204), 1x branche catch (erreur réseau)
});
