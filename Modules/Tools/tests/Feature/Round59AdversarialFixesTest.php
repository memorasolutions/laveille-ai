<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;
use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 59 (2026-07-27) : passe adversariale fraîche sur constructeur-prompts, 1 fix réel.
//
// Round 57 avait corrigé _reloadIfListEmpty() (utilisée par deletePrompt()/toggleFavorite()) pour
// retirer le paramètre "page" de l'URL avant de recharger, évitant d'atterrir sur une page de
// pagination devenue hors-limites. Ce même pattern de reload existait déjà AILLEURS dans le même
// fichier - saveTags() - mais n'avait JAMAIS été corrigé : `setTimeout(function() {
// window.location.reload(); }, 600);` inconditionnel, MÊME URL. Si retirer un tag fait sortir le
// prompt du filtre ?tag=X actif ET que ce prompt était le dernier de la DERNIÈRE page de
// pagination pour ce filtre, le reload atterrit sur ?tag=X&page=N devenu hors-limites : le
// serveur renvoie une collection vide, affiche "Aucun prompt ne correspond à ces filtres." alors
// que des prompts valides existent toujours sur les pages précédentes du même filtre. Fix :
// extraction d'un helper partagé _reloadWithoutPage() (utilisé par _reloadIfListEmpty() ET
// saveTags()), DRY et cohérent avec le fix round 57.

function makeRound59PromptTool(): void
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

it('has a shared _reloadWithoutPage() helper that strips the page param, and saveTags() uses it instead of a raw reload (round 59)', function () {
    makeRound59PromptTool();
    $user = User::factory()->create();
    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt round 59',
        'prompt_text' => 'Texte',
        'public_id' => 'r59tags123',
        'tags' => ['urgent'],
    ]);

    $html = $this->actingAs($user)->get('/user/prompts')->assertOk()->getContent();

    // Le helper partagé doit exister et retirer "page" de l'URL avant de naviguer.
    $helperPos = strpos($html, '_reloadWithoutPage() {');
    expect($helperPos)->not->toBeFalse();
    $helperEndPos = strpos($html, '_reloadIfListEmpty() {', $helperPos) ?: strlen($html);
    $helperBody = substr($html, $helperPos, $helperEndPos - $helperPos);
    expect($helperBody)->toContain('new URL(window.location.href)');
    expect($helperBody)->toContain("url.searchParams.delete('page')");
    expect($helperBody)->toContain('window.location.href = url.toString()');

    // _reloadIfListEmpty() doit maintenant déléguer à _reloadWithoutPage() (pas de duplication).
    $reloadIfEmptyPos = strpos($html, '_reloadIfListEmpty() {');
    expect($reloadIfEmptyPos)->not->toBeFalse();
    $reloadIfEmptyEndPos = strpos($html, 'async saveProfile()', $reloadIfEmptyPos) ?: strlen($html);
    $reloadIfEmptyBody = substr($html, $reloadIfEmptyPos, $reloadIfEmptyEndPos - $reloadIfEmptyPos);
    expect($reloadIfEmptyBody)->toContain('this._reloadWithoutPage.bind(this)');

    // saveTags() doit utiliser le helper partagé, pas un window.location.reload() cru.
    $saveTagsPos = strpos($html, 'async saveTags(publicId, tagsInput)');
    expect($saveTagsPos)->not->toBeFalse();
    $saveTagsEndPos = strpos($html, 'async duplicatePrompt(publicId)', $saveTagsPos) ?: strlen($html);
    $saveTagsBody = substr($html, $saveTagsPos, $saveTagsEndPos - $saveTagsPos);
    expect($saveTagsBody)->toContain('this._reloadWithoutPage.bind(this)');
    expect($saveTagsBody)->not->toContain('window.location.reload()');
});
