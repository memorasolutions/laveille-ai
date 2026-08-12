<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 92 (2026-07-27) : passe adversariale fraîche après le lot round 91 (emoji picker 44px
// mobile). 3 manques réels corrigés - tous la même classe de défaut déjà identifiée au round 86
// (cible tactile WCAG 2.2 AAA SC 2.5.5) mais cette fois sur la classe DE BASE .ct-btn elle-même
// (pas seulement ses variantes -sm/-xs) : seule .ct-btn-accent et .ct-btn-icon ont un plancher
// min-height dans charte.css - tout .ct-btn/.ct-btn-primary/.ct-btn-outline/.ct-btn-outline-danger
// nu (padding:10px 16px + font-size:14px + line-height:1.4 ≈ 39,6px calculé) en est dépourvu.
//
// 1. constructeur-prompts.blade.php : 8 boutons .ct-btn sans plancher min-height (7 signalés par
//    le sous-agent + 1 découvert en vérifiant moi-même le reste de la modale d'aide - le × de
//    fermeture juste avant le bouton "Compris !" explicitement signalé, même défaut, même bloc).
// 2. prompt-anon-panel.js : le bouton "🔒 Masquer mes infos →" (anonBtn) créé dynamiquement,
//    même défaut que dismissBtn déjà corrigé au round 90 dans le même bloc.
// 3. user/prompts/index.blade.php : les chips de tag AFFICHÉES DANS CHAQUE CARTE (distinctes de
//    la barre de filtres globale déjà corrigée au round 88 - ce sont 2 emplacements différents).

it('gives every plain .ct-btn button a 44px min-height floor (round 92)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    // Sauvegarder/Mettre à jour (ligne ~36)
    expect($blade)->toContain('style="white-space:nowrap; min-height:44px;"');

    // Précédent/Suivant (wizard nav) - seuil "step < 4" depuis la restauration du wizard 4 étapes
    // (2026-08-03) ; le plancher 44px lui-même, protégé par ce test, est inchangé.
    expect($blade)->toContain('@click="prevStep()" x-show="step > 1" style="min-height:44px;"');
    expect($blade)->toContain('@click="nextStep()" x-show="step < 4" style="min-height:44px;"');

    // Améliorer avec mon IA / Exporter .txt : binding :style dynamique étendu (pattern round 86)
    expect($blade)->toContain(":style=\"'min-height:44px;' + (!isValid ? 'opacity:0.5;cursor:not-allowed;' : '')\"");

    // Recommencer : depuis le 2026-08-12, ouvre une modale de confirmation (#resetConfirmModal)
    // au lieu de l'ancienne double confirmation par re-clic (armReset, retirée).
    expect($blade)->toContain('@click="jQuery(\'#resetConfirmModal\').modal(\'show\')"');
    expect($blade)->toContain('id="resetConfirmModal"');
    // Pont événementiel Alpine (le point de rupture le plus probable d'une future régression) :
    // la modale vit hors du scope x-data="promptBuilder()", cet écouteur est ce qui la relie.
    expect($blade)->toContain('@cp-reset-confirmed.window="resetAll()"');

    // Modale aide : × de fermeture (auto-découvert) + "Compris !" (signalé)
    expect($blade)->toContain('min-width:44px; min-height:44px; display:flex; align-items:center; justify-content:center;">&times;</button>');
    expect($blade)->toContain('onclick="jQuery(\'#promptHelpModal\').modal(\'hide\')" style="min-height:44px;">{{ __(\'Compris !\') }}</button>');
});

// Test du bouton anonBtn (prompt-anon-panel.js) retiré le 2026-08-04 : le panneau d'anonymisation
// intégré au constructeur de prompts a été retiré (demande explicite de l'utilisateur, séparation
// des deux outils) - prompt-anon-panel.js n'existe plus.

it('gives in-card tag chips a 44px touch target, distinct from the filter bar chips (round 92)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));

    expect($blade)->toContain("background: #E0F2F1; color: #053D4A; padding: 2px 10px; border-radius: 999px; font-weight: 600; font-size: 11px; text-decoration: none; min-height: 44px; box-sizing: border-box; display: inline-flex; align-items: center;");
});

it('renders the constructeur-prompts page with all round 92 button fixes present (real page)', function () {
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

    expect($html)->toContain('style="white-space:nowrap; min-height:44px;"');
    expect($html)->toContain('min-width:44px; min-height:44px; display:flex; align-items:center; justify-content:center;">&times;</button>');
});
