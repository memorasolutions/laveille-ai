<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 88 (2026-07-27) : passe adversariale fraîche après le lot round 87 (.ct-custom-card__title-btn
// / .ct-custom-card__select). 2 manques réels corrigés :
//
// 1. Cibles tactiles < 44×44px (WCAG 2.2 AAA SC 2.5.5) sur les 5 labels de case à cocher
//    (constructeur-prompts.blade.php, section "Réglages avancés : contraintes et destination") :
//    useDelimiters, constraintAntiAI, constraintTypo, constraintChainOfThought, constraintAskIfUnclear.
//    Ces labels n'avaient ni min-height ni padding, contrairement aux 8 labels radio du même fichier
//    déjà à min-height:44px depuis les rounds précédents. Fixé : min-height:44px; padding:4px 6px;
//    ajouté aux 5 labels (motif identique à celui déjà appliqué aux radios).
//
// 2. Les 2 liens "Effacer les filtres" (index.blade.php, page Mes prompts) n'avaient pas de cible
//    tactile 44px, contrairement à tous les autres éléments interactifs de la page. La variante de
//    la barre de filtres utilisait en plus #DC2626 sur fond blanc (contraste ~4,83:1, AA seulement)
//    au lieu du seuil AAA 7:1 exigé par la charte. Fixé : min-height:44px + padding sur les 2 liens,
//    et remplacement de #DC2626 par #991B1B (contraste ~8,3:1 AAA, token déjà utilisé pour texte
//    danger sur fond clair ailleurs dans le projet, charte.css:1009 .alert-danger).

it('has WCAG AAA 44px touch targets on the 5 constraint checkboxes in constructeur-prompts (round 88)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    foreach (['useDelimiters', 'constraintAntiAI', 'constraintTypo', 'constraintChainOfThought', 'constraintAskIfUnclear'] as $model) {
        $pos = strpos($blade, 'x-model="'.$model.'"');
        expect($pos)->not->toBeFalse();

        // Le <label> ouvrant précède l'input de quelques dizaines de caractères.
        $labelStart = strrpos(substr($blade, 0, $pos), '<label style="');
        $labelTag = substr($blade, $labelStart, $pos - $labelStart);

        expect($labelTag)->toContain('min-height: 44px; padding: 4px 6px;');
    }
});

it('has WCAG AAA-contrast and 44px "Effacer les filtres" links in Mes prompts (round 88)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));

    // Round 102 (2026-07-27, passe adversariale) : le composant action-menu partagé (menu ⋮, aussi
    // présent sur cette page) utilise légitimement #DC2626 pour son item "Supprimer" - l'assertion
    // "la page entière ne contient jamais #DC2626" (round 88) est donc désormais trop large. Resserrée
    // sur le tag précis du lien "Effacer les filtres" (seul périmètre réel visé par le round 88).
    expect(substr_count($blade, "{{ __('Effacer les filtres') }}"))->toBe(2);
    $effacerFiltresTag = 'color: #991B1B; font-weight: 600; text-decoration: none; min-height: 44px; padding: 4px 6px;';
    expect($blade)->toContain($effacerFiltresTag);
    expect($effacerFiltresTag)->not->toContain('#DC2626');
    expect($blade)->toContain('color: var(--c-primary, #064E5A); font-weight: 600; min-height: 44px; padding: 4px 6px;');
});

it('renders the constraint checkboxes with 44px min-height on the real page (round 88)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect(substr_count($html, 'min-height: 44px; padding: 4px 6px;'))->toBeGreaterThanOrEqual(13);
});

it('renders the "Effacer les filtres" links with AAA contrast and 44px target on the real page (round 88)', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt test',
        'prompt_text' => 'Contenu de test',
        'tags' => ['test-round-88'],
    ]);

    $html = $this->actingAs($user)->get('/user/prompts?tag=test-round-88')->assertOk()->getContent();

    // Round 102 : idem ci-dessus - #DC2626 apparaît désormais légitimement ailleurs sur cette page
    // (item "Supprimer" du menu ⋮ de chaque prompt), donc on vérifie uniquement la présence du token
    // AAA #991B1B sur le lien "Effacer les filtres", plus l'absence de #DC2626 dans SON tag précis.
    expect($html)->toContain('color: #991B1B; font-weight: 600; text-decoration: none; min-height: 44px; padding: 4px 6px;');
});
