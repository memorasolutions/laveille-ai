<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;
use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 52 (2026-07-27) : passe adversariale fraîche sur constructeur-prompts, 1 fix réel.
//
// deletePrompt() et le chemin de retrait de toggleFavorite() (sous le filtre "Favoris
// seulement", round 51) ne faisaient QUE card.remove() sans jamais recharger la page. Si la
// carte retirée était la DERNIÈRE visible (dernière page de pagination, ou dernier favori sous
// ?favorite=1), la grille devenait un espace blanc : le message "Aucun prompt" (bloc
// @if($prompts->isEmpty()) évalué une seule fois côté serveur) ne s'affichait jamais, et les
// liens de pagination restaient figés à leur état de rendu initial (obsolète). Fix :
// _reloadIfListEmpty() vérifie s'il reste au moins une carte dans le DOM après le retrait ; si
// la grille est vide, elle recharge la page pour laisser le serveur re-rendre l'état correct
// (empty-state + pagination à jour). Appelée dans les deux chemins de retrait.

function makeRound52PromptTool(): void
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

it('reloads the page when the last card is removed via deletePrompt, so the empty-state and pagination re-render correctly (round 52)', function () {
    makeRound52PromptTool();
    $user = User::factory()->create();
    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt round 52',
        'prompt_text' => 'Texte',
        'public_id' => 'r52delete123',
    ]);

    $this->actingAs($user)->get('/user/prompts')->assertOk();

    // Tâche #1416 (2026-08-02) : cette logique vit désormais dans le fichier extrait
    // public/assets/tools/user-prompts/user-prompts-core.js (Alpine.data), plus dans le HTML rendu.
    $js = file_get_contents(public_path('assets/tools/user-prompts/user-prompts-core.js'));

    // _reloadIfListEmpty() doit exister et être appelée dans deletePrompt(), après card.remove().
    $helperDefPos = strpos($js, '_reloadIfListEmpty() {');
    $deleteFnPos = strpos($js, 'async deletePrompt(publicId)');
    $cardRemovePos = strpos($js, 'if (card) card.remove();', $deleteFnPos);
    $reloadCallPos = strpos($js, 'this._reloadIfListEmpty();', $cardRemovePos);

    expect($helperDefPos)->not->toBeFalse();
    expect($deleteFnPos)->not->toBeFalse();
    expect($cardRemovePos)->not->toBeFalse();
    expect($reloadCallPos)->not->toBeFalse();
    expect($cardRemovePos)->toBeGreaterThan($deleteFnPos);
    expect($reloadCallPos)->toBeGreaterThan($cardRemovePos);

    // Le helper vérifie le DOM (aucune carte restante) avant de recharger.
    expect($js)->toContain('document.querySelector(\'article[id^="prompt-card-"]\')');
});

it('reloads the page when the last favorite card is removed under the ?favorite=1 filter (round 52)', function () {
    makeRound52PromptTool();
    $user = User::factory()->create();
    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt round 52 fav',
        'prompt_text' => 'Texte',
        'public_id' => 'r52fav123',
        'is_favorite' => true,
    ]);

    $this->actingAs($user)->get('/user/prompts')->assertOk();

    // Tâche #1416 (2026-08-02) : cette logique vit désormais dans le fichier extrait
    // public/assets/tools/user-prompts/user-prompts-core.js (Alpine.data), plus dans le HTML rendu.
    $js = file_get_contents(public_path('assets/tools/user-prompts/user-prompts-core.js'));

    // this._reloadIfListEmpty() doit être appelée après favCard.remove() dans le chemin de
    // retrait sous filtre (round 51), pas ailleurs dans le fichier.
    $favRemovePos = strpos($js, 'if (favCard) favCard.remove();');
    $reloadCallPos = strpos($js, 'this._reloadIfListEmpty();', $favRemovePos);

    expect($favRemovePos)->not->toBeFalse();
    expect($reloadCallPos)->not->toBeFalse();
    expect($reloadCallPos)->toBeGreaterThan($favRemovePos);
});
