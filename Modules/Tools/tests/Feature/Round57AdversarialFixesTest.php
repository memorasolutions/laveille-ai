<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;
use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 57 (2026-07-27) : passe adversariale fraîche sur constructeur-prompts, 1 fix réel.
//
// _reloadIfListEmpty() (round 52) rechargeait la MÊME URL, donc le MÊME ?page=N, après avoir
// vidé la grille. Si la carte retirée était la dernière de la DERNIÈRE page de pagination
// (paginate(20) sans borne, UserPromptController::index), ?page=N devenait hors-limites :
// LengthAwarePaginator renvoie une collection VIDE pour cette page, alors que des prompts
// valides restent sur les pages précédentes. Sans filtre actif, le template rend la branche
// @else « Aucun prompt sauvegardé » avec pour seul CTA « Créer un prompt » - AUCUN lien de
// retour vers la page 1 - un message trompeur qui laisse croire à l'utilisateur qu'il a tout
// perdu. Fix : retirer le paramètre "page" de l'URL avant de recharger (via URL/URLSearchParams),
// garantissant un atterrissage sur une page toujours valide tout en conservant les autres
// filtres actifs (search/tag/favorite).
//
// Round 59 (2026-07-27) : cette logique a été extraite dans un helper partagé
// _reloadWithoutPage() (le même défaut existait aussi dans saveTags(), jamais corrigé au round
// 57) - _reloadIfListEmpty() délègue maintenant à ce helper au lieu de dupliquer la logique.

function makeRound57PromptTool(): void
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

it('strips the page query param before reloading in _reloadIfListEmpty(), so a suppression on the last page never lands on a stale out-of-range page (round 57)', function () {
    makeRound57PromptTool();
    $user = User::factory()->create();
    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt round 57',
        'prompt_text' => 'Texte',
        'public_id' => 'r57reload123',
    ]);

    $this->actingAs($user)->get('/user/prompts')->assertOk();

    // Tâche #1416 (2026-08-02) : cette logique vit désormais dans le fichier extrait
    // public/assets/tools/user-prompts/user-prompts-core.js (Alpine.data), plus dans le HTML rendu.
    $js = file_get_contents(public_path('assets/tools/user-prompts/user-prompts-core.js'));

    // Round 59 : la logique de retrait de "page" vit maintenant dans _reloadWithoutPage()
    // (partagée avec saveTags()) ; _reloadIfListEmpty() y délègue au lieu de la dupliquer.
    $sharedHelperPos = strpos($js, '_reloadWithoutPage() {');
    expect($sharedHelperPos)->not->toBeFalse();
    $sharedHelperEndPos = strpos($js, '_reloadIfListEmpty() {', $sharedHelperPos) ?: strlen($js);
    $sharedHelperBody = substr($js, $sharedHelperPos, $sharedHelperEndPos - $sharedHelperPos);
    expect($sharedHelperBody)->toContain('new URL(window.location.href)');
    expect($sharedHelperBody)->toContain("url.searchParams.delete('page')");
    expect($sharedHelperBody)->toContain('window.location.href = url.toString()');

    $helperPos = strpos($js, '_reloadIfListEmpty() {');
    expect($helperPos)->not->toBeFalse();

    // _reloadIfListEmpty() ne doit plus contenir un simple window.location.reload() (round 52,
    // buggé sur la dernière page) : il doit déléguer à _reloadWithoutPage().
    $nextFnPos = strpos($js, 'async saveProfile()', $helperPos) ?: (strlen($js));
    $helperBody = substr($js, $helperPos, $nextFnPos - $helperPos);

    expect($helperBody)->toContain('this._reloadWithoutPage.bind(this)');
    expect($helperBody)->not->toContain('window.location.reload()');
});
