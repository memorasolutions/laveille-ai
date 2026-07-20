<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Tool::firstOrCreate(['slug' => 'prompteur'], [
        'name' => 'Prompteur',
        'description' => 'Téléprompteur gratuit avec éditeur de script structuré en sections (indication visuelle/action + texte à dire ou grandes lignes, au choix), défilement synchronisé à votre débit et générateur de méta-prompt à copier dans l\'IA de votre choix pour créer le contenu automatiquement. Sans compte, 100 % dans le navigateur.',
        'icon' => '🎬',
        'sort_order' => 16,
        'is_active' => true,
        'is_under_construction' => true,
        'category' => 'productivite',
    ]);
});

it('renders prompteur tool page with required DOM markers', function () {
    Tool::where('slug', 'prompteur')->update(['is_under_construction' => false]);

    $response = $this->get('/outils/prompteur');

    $response->assertStatus(200);
    $response->assertSee('Prompteur', escape: false);

    // Gabarit "carte unique" identique aux autres outils (minuteur-visuel, etc.).
    $response->assertSee('col-lg-10', escape: false);

    // 3 onglets (ARIA tablist) : BYOA, Import/Édition, Téléprompteur.
    $response->assertSee('role="tablist"', escape: false);
    $response->assertSee('id="prompteur-tablist"', escape: false);
    $response->assertSee('id="prompteur-tab-byoa"', escape: false);
    $response->assertSee('id="prompteur-tab-edit"', escape: false);
    $response->assertSee('id="prompteur-tab-teleprompter"', escape: false);
    $response->assertSee('Générer avec votre IA', escape: false);
    $response->assertSee('Importer / Éditer le script', escape: false);
    $response->assertSee('Téléprompteur', escape: false);

    // Onglet 1 — BYOA : formulaire de méta-prompt.
    $response->assertSee('id="prompteur-goal-textarea"', escape: false);
    $response->assertSee('id="prompteur-audience-input"', escape: false);
    $response->assertSee('id="prompteur-duration-select"', escape: false);
    $response->assertSee('id="prompteur-tone-select"', escape: false);
    $response->assertSee('id="prompteur-generated-prompt-textarea"', escape: false);
    $response->assertSee('id="prompteur-copy-prompt-btn"', escape: false);
    $response->assertSee('Copier le prompt', escape: false);

    // Onglet 2 — Import/Édition : zone d'import + gabarits de sections.
    $response->assertSee('id="prompteur-import-textarea"', escape: false);
    $response->assertSee('id="prompteur-import-btn"', escape: false);
    $response->assertSee('id="prompteur-paste-clipboard-btn"', escape: false);
    $response->assertSee('id="prompteur-sections-list"', escape: false);
    $response->assertSee('id="prompteur-add-section-btn"', escape: false);
    $response->assertSee('id="prompteur-goto-teleprompter-btn"', escape: false);

    // Onglet 3 — Téléprompteur : zone de lecture + contrôles.
    $response->assertSee('id="prompteur-reading-area"', escape: false);
    $response->assertSee('id="prompteur-reading-content"', escape: false);
    $response->assertSee('id="prompteur-play-pause-btn"', escape: false);
    $response->assertSee('id="prompteur-speed-slider"', escape: false);
    $response->assertSee('id="prompteur-progress-bar"', escape: false);
    $response->assertSee('id="prompteur-keyboard-shortcuts-legend"', escape: false);

    // Réglages du Prompteur (panneau modal).
    $response->assertSee('id="prompteur-settings-toggle-btn"', escape: false);
    $response->assertSee('id="prompteur-settings-panel"', escape: false);
    $response->assertSee('Réglages et personnalisation', escape: false);

    // Annonces ARIA sobres.
    $response->assertSee('aria-live="polite"', escape: false);

    // JSON-LD WebApplication (via tool-geo, pas SoftwareApplication).
    $response->assertSee('WebApplication', escape: false);

    // Fichiers statiques référencés.
    $response->assertSee('assets/tools/prompteur/prompteur-core.js', escape: false);
    $response->assertSee('assets/tools/prompteur/prompteur.css', escape: false);
});

it('exposes the 4 starter templates and the teleprompter controls', function () {
    Tool::where('slug', 'prompteur')->update(['is_under_construction' => false]);

    $response = $this->get('/outils/prompteur');

    $response->assertStatus(200);

    // Les 4 boutons de gabarits de sections prêts à l'emploi.
    $response->assertSee('id="prompteur-template-tutoriel"', escape: false);
    $response->assertSee('id="prompteur-template-actualites"', escape: false);
    $response->assertSee('id="prompteur-template-formation"', escape: false);
    $response->assertSee('id="prompteur-template-vide"', escape: false);
    $response->assertSee('Tutoriel logiciel', escape: false);
    $response->assertSee('Capsule actualités', escape: false);
    $response->assertSee('Formation / cours', escape: false);

    // Panneau de réglages : thème, contraste, taille de texte, vue compacte, animations réduites.
    $response->assertSee('id="prompteur-theme-clair"', escape: false);
    $response->assertSee('id="prompteur-theme-sombre"', escape: false);
    $response->assertSee('id="prompteur-theme-systeme"', escape: false);
    $response->assertSee('id="prompteur-high-contrast-toggle"', escape: false);
    $response->assertSee('id="prompteur-text-size-select"', escape: false);
    $response->assertSee('id="prompteur-compact-view-toggle"', escape: false);
    $response->assertSee('id="prompteur-reduced-motion-toggle"', escape: false);

    // Contrôles du téléprompteur : vitesse, taille du texte de lecture, contraste, miroir, voix.
    $response->assertSee('id="prompteur-font-decrease-btn"', escape: false);
    $response->assertSee('id="prompteur-font-increase-btn"', escape: false);
    $response->assertSee('id="prompteur-contrast-toggle-btn"', escape: false);
    $response->assertSee('id="prompteur-mirror-toggle-btn"', escape: false);
    $response->assertSee('id="prompteur-voice-scroll-btn"', escape: false);
    $response->assertSee('Mode miroir', escape: false);
});

it('renders the page repeatedly without side effects on plain GET', function () {
    Tool::where('slug', 'prompteur')->update(['is_under_construction' => false]);

    // Le Prompteur ne supporte pas de query params de partage server-side (tout l'état
    // vit côté client dans Alpine/localStorage) — on vérifie plutôt la robustesse d'appels
    // GET répétés (pas d'effet de bord serveur, réponse stable).
    $first = $this->get('/outils/prompteur');
    $first->assertStatus(200);
    $first->assertSee('Prompteur', escape: false);

    $second = $this->get('/outils/prompteur');
    $second->assertStatus(200);
    $second->assertSee('Prompteur', escape: false);

    expect(Tool::where('slug', 'prompteur')->count())->toBe(1);
});

it('respects is_under_construction flag for non-admin user', function () {
    Tool::where('slug', 'prompteur')->update(['is_under_construction' => true]);

    $response = $this->get('/outils/prompteur');

    $response->assertStatus(200);
    $response->assertSee('En construction', escape: false);
    $response->assertDontSee('id="prompteur-app-root"', escape: false);
});

it('serves prompteur in tools public index when active', function () {
    Tool::where('slug', 'prompteur')->update(['is_under_construction' => false, 'is_active' => true]);

    $response = $this->get('/outils');

    $response->assertStatus(200);
    $response->assertSee('Prompteur', escape: false);
});
