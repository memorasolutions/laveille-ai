<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 118 (2026-07-27) : passe adversariale fraîche après le round 117. 1 manque réel de
// GRAVITÉ HAUTE - perte de données utilisateur - corrigé.
//
// _loadCustomCards() (constructeur-prompts-core.js) était le SEUL des 8 points réseau du fichier
// à ne jamais signaler son échec (les 7 autres appellent _showSaveError). Son premier .then
// faisait `return r.ok ? r.json() : null;` : sur 401/403/429/500 la chaîne continuait avec
// data=null au lieu de lever. Conséquence : customCards retombait à [] et customCardsLoaded
// passait quand même à true (y compris dans le .catch).
//
// L'utilisateur voyait simplement « aucune carte personnalisée » - plausible, donc invisible
// comme anomalie. Il ajoutait alors une carte : addCustomCard() ne teste que
// `!this.customCardsLoaded` (déjà true à tort), donc rien ne bloquait. persistCustomCards()
// envoyait custom_cards = [1 seule carte]. Côté serveur, ToolPreferenceController::update() fait
// array_merge($prefs[$tool] ?? [], [$key => $value]) : remplacement COMPLET de la clé, pas une
// fusion élément par élément. Résultat : toutes les cartes personnalisées déjà enregistrées
// étaient écrasées et définitivement perdues, sans qu'aucune erreur n'ait jamais été affichée à
// aucun moment du parcours. Cela viole la règle « jamais de suppression de données utilisateurs ».
//
// Correctif : on lève sur !r.ok ; en cas d'échec customCardsLoaded reste FALSE (c'est LUI qui
// garde addCustomCard et le bouton, donc aucune écriture destructrice n'est possible) et
// customCardsLoadFailed passe à true ; un avertissement persistant role="alert" explique la
// situation et offre un bouton « Réessayer » (retryLoadCustomCards). Un toast _showSaveError à
// 4 secondes aurait été insuffisant : le bouton reste désactivé bien après sa disparition.

it('throws instead of silently continuing when the custom cards request fails (round 118)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain("if (!r.ok) { throw new Error('HTTP ' + r.status); }");

    // L'assertion est SCOPÉE au corps de _loadCustomCards : le motif `r.ok ? r.json() : null`
    // subsiste volontairement ligne ~565, dans la branche d'autofill « Mon profil ». Là, son
    // échec est bénin (`if (!profile) return;` = simple absence de pré-remplissage, aucune
    // perte de données ni faux état persisté), contrairement à _loadCustomCards où il ouvrait
    // l'écrasement. Interdire le motif dans TOUT le fichier serait une sur-correction.
    $start = strpos($js, '_loadCustomCards: function() {');
    $end = strpos($js, 'retryLoadCustomCards: function() {');
    expect($start)->not->toBeFalse();
    expect($end)->not->toBeFalse();

    $body = substr($js, $start, $end - $start);
    expect($body)->not->toContain('return r.ok ? r.json() : null;');
    expect($body)->toContain('self.customCardsLoadFailed = true;');
});

it('keeps customCardsLoaded false on failure so the destructive add path stays blocked (round 118)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->not->toContain('.catch(function() { self.customCardsLoaded = true; });');
    expect($js)->toContain('self.customCardsLoadFailed = true;');
    expect($js)->toContain('customCardsLoadFailed: false,');
});

it('still guards addCustomCard on customCardsLoaded (round 118 invariant)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($js)->toContain('if (!this.customCardsLoaded || this.customCards.length >= 10) return;');
    expect($blade)->toContain(':disabled="!customCardsLoaded || customCards.length >= 10"');
});

it('exposes an explicit retry entry point (round 118)', function () {
    $js = file_get_contents(base_path('public/assets/tools/constructeur-prompts/constructeur-prompts-core.js'));
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($js)->toContain('retryLoadCustomCards: function() {');
    expect($js)->toContain('this._loadCustomCards();');
    expect($blade)->toContain('@click="retryLoadCustomCards()"');
});

it('shows a persistent accessible warning instead of a transient toast (round 118)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    expect($blade)->toContain('x-if="isAuthenticated && customCardsLoadFailed"');
    expect($blade)->toContain('role="alert"');
    expect($en)->toHaveKey("Impossible de charger vos cartes personnalisées pour le moment. L'ajout est désactivé afin de ne pas écraser celles déjà enregistrées.");
    expect($en)->toHaveKey('Réessayer');
});

it('renders the constructeur-prompts page correctly after the round 118 fix (real page, no regression)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk();
});
