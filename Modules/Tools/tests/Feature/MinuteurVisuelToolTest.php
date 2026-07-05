<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Tool::firstOrCreate(['slug' => 'minuteur-visuel'], [
        'name' => 'Minuteur visuel',
        'description' => 'Minuteur visuel gratuit avec 5 styles animés, présélections rapides et alertes sonores.',
        'icon' => '⏱️',
        'sort_order' => 14,
        'is_active' => true,
        'is_under_construction' => true,
        'category' => 'productivite',
    ]);
});

it('renders minuteur-visuel tool page with required DOM markers', function () {
    Tool::where('slug', 'minuteur-visuel')->update(['is_under_construction' => false]);

    $response = $this->get('/outils/minuteur-visuel');

    $response->assertStatus(200);
    $response->assertSee('Minuteur visuel', escape: false);

    // Uniformisation de mise en page avec les autres outils "outils gratuits"
    // (generateur-mots-passe/tirage-presentations) : pas d'icône emoji dans le H1
    // (uniquement $tool->name, comme les autres).
    // Note : le fond #f8f9fa "chunké" (.mv-panel) a été essayé puis retiré (#705,
    // effet "pains empilés" signalé par l'utilisateur) — pas d'assertion dessus.
    // Note #713/#714 (2026-07-05) : container-fluid/grille 2 colonnes (#708/#711) retirés —
    // gabarit identique aux autres outils "carte unique". #714 : l'ancien col-lg-8 laissait
    // un espace vide identique (au pixel) sur generateur-mots-passe/tirage-presentations —
    // pas un bug isolé, donc élargi SITE-WIDE (6 fichiers) à col-lg-10 plutôt que de créer
    // une nouvelle exception pour le seul minuteur.
    $response->assertSee('col-lg-10', escape: false);
    $response->assertDontSee('⏱️ Minuteur visuel', escape: false);

    // 5 styles visuels dans un radiogroup.
    $response->assertSee('role="radiogroup"', escape: false);
    $response->assertSee('Disque', escape: false);
    $response->assertSee('Sablier', escape: false);
    $response->assertSee('Anneau', escape: false);
    $response->assertSee('Chiffres', escape: false);
    $response->assertSee('Feu de circulation', escape: false);

    // Présélections nommées (5/10/15/25/45 + Pomodoro).
    $response->assertSee('5 min', escape: false);
    $response->assertSee('10 min', escape: false);
    $response->assertSee('15 min', escape: false);
    $response->assertSee('25 min', escape: false);
    // #751-758 : 45 min retiré (demande utilisateur) - jamais présent dans le DOM.
    $response->assertDontSee('45 min', escape: false);
    $response->assertSee('Pomodoro 25 min', escape: false);
    $response->assertSee('Pause 5 min', escape: false);

    // Durée personnalisée — saisie exacte en minutes, hors présélections.
    $response->assertSee('id="mvCustomMinutes"', escape: false);
    $response->assertSee('Définir', escape: false);

    // Légende du feu de circulation — 3 lignes dynamiques (seuils dérivés de totalSeconds)
    // + rappel que le chiffre au centre reste le temps exact restant.
    $response->assertSee('class="mv-traffic-legend"', escape: false);
    $response->assertSee('class="mv-traffic-legend__line"', escape: false);
    $response->assertSee('class="mv-traffic-legend__note"', escape: false);
    $response->assertSee('Plus de la moitié du temps', escape: false);
    $response->assertSee('trafficGreenThreshold', escape: false);
    $response->assertSee('trafficYellowThreshold', escape: false);
    $response->assertSee('trafficTotalFormatted', escape: false);

    // Sablier réaliste — dégradé de sable, texture de grain (feTurbulence) et verre dégradé.
    $response->assertSee('id="mvSandGradient"', escape: false);
    $response->assertSee('id="mvGlassGradient"', escape: false);
    $response->assertSee('id="mvSandGrain"', escape: false);
    $response->assertSee('feTurbulence', escape: false);
    $response->assertSee('class="mv-hourglass-sand-surface"', escape: false);

    // Checkbox Réglages visibles (convention charte display:inline-block !important,
    // cf. tirage-presentations/generateur-mots-passe) + suffixe "s" du seuil d'alerte.
    $response->assertSee('display:inline-block !important; width:20px; height:20px', escape: false);
    $response->assertSee('id="mvWarningThreshold"', escape: false);

    // #712 — durées Pomodoro configurables (Réglages) : pilotent dynamiquement les 2 presets
    // Pomodoro (fallback SSR = valeurs par défaut 25/5, cf. x-text avec contenu de repli).
    $response->assertSee('id="mvPomodoroFocus"', escape: false);
    $response->assertSee('id="mvPomodoroBreak"', escape: false);
    $response->assertSee('pomodoroFocusMin', escape: false);
    $response->assertSee('pomodoroBreakMin', escape: false);

    // #765-770 — 9 grains (jitter/taille/vitesse variables via variables CSS --gx/--gd/--gdur)
    // qui tombent visiblement à travers le goulot du sablier pendant le décompte.
    $response->assertSee('class="mv-sand-stream"', escape: false);
    $sandGrainCount = substr_count($response->getContent(), 'class="mv-sand-grain-particle"');
    expect($sandGrainCount)->toBe(9);
    $response->assertSee('--gx:', escape: false);
    $response->assertSee('--gdur:', escape: false);

    // Annonces ARIA sobres.
    $response->assertSee('aria-live="polite"', escape: false);

    // JSON-LD WebApplication (via tool-geo, pas SoftwareApplication).
    $response->assertSee('WebApplication', escape: false);

    // Fichiers statiques référencés.
    $response->assertSee('assets/tools/minuteur-visuel/minuteur-visuel-core.js', escape: false);
    $response->assertSee('assets/tools/minuteur-visuel/minuteur-visuel.css', escape: false);

    // #744-750 — couleurs personnalisées récentes (connectés) + incitation à se connecter (invités).
    $response->assertSee('class="mv-login-hint"', escape: false);
    $response->assertSee('Connectez-vous pour retrouver vos couleurs personnalisées', escape: false);

    // #787-792 — étoile de favoris couleur (max 2), distincte de l'historique roulant récent.
    $response->assertSee('class="mv-color-favorite-toggle"', escape: false);
    $response->assertSee('toggleFavoriteColor()', escape: false);
    $response->assertSee('class="mv-favorite-colors"', escape: false);

    // #797-801 — seuils du feu de circulation configurables (profils préréglés + personnalisé).
    $response->assertSee('class="mv-traffic-profiles"', escape: false);
    $response->assertSee('setTrafficProfile(key)', escape: false);
    $response->assertSee('class="mv-traffic-custom"', escape: false);
    $response->assertSee('Connectez-vous pour choisir vos propres seuils du feu de circulation', escape: false);

    // #802-805 — confirmation immédiate du profil appliqué (le feu reste vert tant que le
    // décompte n'a pas commencé - sans cette ligne, aucune preuve visible que le clic a agi).
    $response->assertSee('class="mv-traffic-applied-hint"', escape: false);

    // #751-758 — Pomodoro/Pause sur leur propre groupe (ligne déliberée) + incitation à se
    // connecter pour épingler des durées personnalisées (invités).
    $response->assertSee('Présélections Pomodoro', escape: false);
    $response->assertSee('Connectez-vous pour épingler jusqu&#039;à 2 durées personnalisées', escape: false);
});

it('exposes the curated 6-tone color palette in the DOM', function () {
    Tool::where('slug', 'minuteur-visuel')->update(['is_under_construction' => false]);

    $response = $this->get('/outils/minuteur-visuel');

    $response->assertStatus(200);
    $response->assertSee('#991B1B', escape: false);
    $response->assertSee('#064E5A', escape: false);
    $response->assertSee('#6B21A8', escape: false);
    $response->assertSee('#1E40AF', escape: false);
    // #776-781 : « orange » retiré de la palette curatée (remplacé par 2 teintes pâles tendance
    // 2026). Note : #9A2A06 reste présent ailleurs dans le DOM (--c-accent, couleur de marque
    // globale du site, sans lien avec cette palette) - pas d'assertDontSee dessus.
    $response->assertSee('#E8A9AE', escape: false);
    $response->assertSee('#DCC3A0', escape: false);
});

it('accepts share query params without breaking server render', function () {
    Tool::where('slug', 'minuteur-visuel')->update(['is_under_construction' => false]);

    $response = $this->get('/outils/minuteur-visuel?minutes=15&style=ring');

    $response->assertStatus(200);
    $response->assertSee('Minuteur visuel', escape: false);
});

it('respects is_under_construction flag for non-admin user', function () {
    Tool::where('slug', 'minuteur-visuel')->update(['is_under_construction' => true]);

    $response = $this->get('/outils/minuteur-visuel');

    $response->assertStatus(200);
    $response->assertSee('En construction', escape: false);
});

it('serves minuteur-visuel in tools public index when active', function () {
    Tool::where('slug', 'minuteur-visuel')->update(['is_under_construction' => false, 'is_active' => true]);

    $response = $this->get('/outils');

    $response->assertStatus(200);
    $response->assertSee('Minuteur visuel', escape: false);
});
