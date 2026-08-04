<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;
use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 51 (2026-07-27) : passe adversariale fraîche sur constructeur-prompts, 2 fixes réels.
//
// Fix 1 : sur la vue filtrée "Favoris seulement" (?favorite=1), retirer un favori via le bouton
// étoile mettait à jour l'icône/aria-pressed/toast mais laissait la carte visible dans une liste
// qui prétend n'afficher que les favoris (ni card.remove() ni reload, contrairement à
// saveTags()/duplicatePrompt() qui rechargent la page). Fix : toggleFavorite() lit le paramètre
// ?favorite de l'URL courante et retire la carte du DOM quand on désactive un favori sous ce filtre.
//
// Fix 2 : le compteur d'en-tête ("X prompts sauvegardés") était un texte Blade figé au rendu
// serveur - il ne changeait plus après une suppression (deletePrompt() retire la carte du DOM sans
// jamais recharger la page). Fix : compteur rendu via Alpine (promptCountLabel()), décrémenté
// explicitement dans deletePrompt() et dans le nouveau chemin de retrait de toggleFavorite().

function makePromptTool(): void
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

it('reads the favorite filter from the URL and removes the card on unfavorite under it (round 51)', function () {
    makePromptTool();
    $user = User::factory()->create();
    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt round 51',
        'prompt_text' => 'Texte',
        'public_id' => 'r51fav123',
        'is_favorite' => true,
    ]);

    $this->actingAs($user)->get('/user/prompts')->assertOk();

    // Tâche #1416 (2026-08-02) : toggleFavorite() vit désormais dans le fichier extrait
    // public/assets/tools/user-prompts/user-prompts-core.js (Alpine.data), plus dans le HTML rendu.
    $js = file_get_contents(public_path('assets/tools/user-prompts/user-prompts-core.js'));

    expect($js)->toContain("new URLSearchParams(window.location.search).get('favorite') === '1'");
    expect($js)->toContain("var favCard = document.getElementById('prompt-card-' + publicId);");
    expect($js)->toContain('if (favCard) favCard.remove();');
});

it('makes the header prompt counter reactive instead of a value frozen at server render (round 51)', function () {
    makePromptTool();
    $user = User::factory()->create();
    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt round 51',
        'prompt_text' => 'Texte',
        'public_id' => 'r51counter123',
    ]);

    $html = $this->actingAs($user)->get('/user/prompts')->assertOk()->getContent();

    // Le compteur passe par Alpine (x-text), pas seulement un texte Blade figé au chargement -
    // cette partie reste dans le markup Blade, inchangée par la tâche #1416.
    expect($html)->toContain('x-text="promptCountLabel()"');
    // Tâche #1416 (2026-08-02) : le compteur n'est plus un littéral `promptCount: 1,` inline - il
    // arrive désormais par le config JSON sérialisé dans x-data="promptsLibrary(@js(...))". Blade
    // @js() encode via JSON.parse('...') et échappe chaque guillemet interne en " (littéral,
    // pour survivre à la fois au JS et à l'attribut HTML), donc la clé apparaît sous cette forme.
    expect($html)->toContain('\\u0022promptCount\\u0022:1');

    // promptCountLabel() (la fonction elle-même) vit désormais dans le fichier extrait.
    $js = file_get_contents(public_path('assets/tools/user-prompts/user-prompts-core.js'));
    expect($js)->toContain('promptCountLabel() {');
});

it('decrements the reactive counter on delete, since deletePrompt() never reloads the page (round 51)', function () {
    makePromptTool();
    $user = User::factory()->create();
    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt round 51',
        'prompt_text' => 'Texte',
        'public_id' => 'r51delete123',
    ]);

    $this->actingAs($user)->get('/user/prompts')->assertOk();

    // Tâche #1416 (2026-08-02) : deletePrompt() vit désormais dans le fichier extrait
    // public/assets/tools/user-prompts/user-prompts-core.js (Alpine.data), plus dans le HTML rendu.
    $js = file_get_contents(public_path('assets/tools/user-prompts/user-prompts-core.js'));

    // La ligne de décrément doit être atteignable DANS deletePrompt(), pas seulement présente
    // ailleurs dans le fichier - on vérifie qu'elle apparaît bien après le card.remove() de
    // deletePrompt() et avant le toast "Prompt supprimé.".
    $deleteFnPos = strpos($js, 'async deletePrompt(publicId)');
    $cardRemovePos = strpos($js, "if (card) card.remove();", $deleteFnPos);
    $decrementPos = strpos($js, 'this.promptCount = Math.max(0, this.promptCount - 1);', $cardRemovePos);
    // Les textes traduits arrivent désormais par `i18n.xxx` (config), plus par un littéral inline -
    // on vérifie l'appel this._toast( plutôt que le texte littéral du toast.
    $toastPos = strpos($js, 'this._toast(', $decrementPos);

    expect($deleteFnPos)->not->toBeFalse();
    expect($cardRemovePos)->not->toBeFalse();
    expect($decrementPos)->not->toBeFalse();
    expect($toastPos)->not->toBeFalse();
    expect($cardRemovePos)->toBeGreaterThan($deleteFnPos);
    expect($decrementPos)->toBeGreaterThan($cardRemovePos);
    expect($toastPos)->toBeGreaterThan($decrementPos);
});
