<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Preuve HTTP de bout en bout de la section publique « Outils liés » (ticket #2524, étape 3 -
 * la partie visible du plan glossaire). Cette section liste, sur la fiche publique d'un terme,
 * les outils de l'annuaire associés via le pivot CURATÉ term_tool (aucune détection automatique -
 * un humain pose l'association dans l'admin), filtrés sur l'état de l'outil lui-même (published
 * + notArchived, mêmes scopes que toute liste publique de l'annuaire). Voir
 * Modules\Dictionary\Models\Term::tools(), Modules\Directory\Models\Tool::terms() et
 * Modules/Dictionary/resources/views/public/show.blade.php.
 *
 * Convention du module (mêmes helpers que TermDansActualiteTest et ViewCounterDictionaryTest) :
 * pas de TermFactory ni de ToolFactory, construction directe des modèles.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Dictionary\Models\Term;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Ol pour éviter tout conflit inter-fichiers) ────────────────────

function olTerm(array $overrides = []): Term
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $slug = 'terme-outils-lies-'.uniqid();

    return Term::create(array_merge([
        'name' => [$locale => 'Terme outils liés '.uniqid(), 'fr' => 'Terme outils liés'],
        'slug' => [$locale => $slug, 'fr' => $slug],
        'definition' => [$locale => 'Définition de test pour la section outils liés.', 'fr' => 'Définition de test.'],
        'is_published' => true,
    ], $overrides));
}

function olTool(array $overrides = []): Tool
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $suffix = uniqid();
    $slug = 'outil-outils-lies-'.$suffix;

    // withoutEvents : même précaution que NewsArticleToolFrontTest (évite les observateurs
    // d'indexation/notification déclenchés par la création d'un outil en test).
    return Tool::withoutEvents(fn () => Tool::create(array_merge([
        'name' => [$locale => 'Outil test '.$suffix, 'fr' => 'Outil test '.$suffix],
        'slug' => [$locale => $slug, 'fr' => $slug],
        'status' => 'published',
        'pricing' => 'free',
    ], $overrides)));
}

/** Attache un outil au terme via le pivot curaté term_tool (aucune colonne source/approbation). */
function olLier(Term $term, Tool $tool): void
{
    $term->tools()->attach($tool->id);
}

// ── Cas 1 : outil curé et publié → section affichée, nom + lien vers la fiche annuaire ─────

it('affiche la section « Outils liés » avec le nom de l\'outil et un lien vers sa fiche annuaire', function () {
    $term = olTerm();
    $tool = olTool(['name' => ['fr_CA' => 'Outil curé visible', 'fr' => 'Outil curé visible']]);
    olLier($term, $tool);

    $reponse = $this->get('/glossaire/'.$term->slug);

    $reponse->assertOk();
    $reponse->assertSee('Outils liés');
    $reponse->assertSee('Outil curé visible');
    $reponse->assertSee($tool->getPublicUrl(), false);
});

// ── Témoin négatif : un terme sans aucune association n'émet AUCUN balisage de section ─────

it('n\'émet AUCUN balisage de section pour un terme sans aucun outil associé (témoin négatif)', function () {
    $term = olTerm();

    $reponse = $this->get('/glossaire/'.$term->slug);

    $reponse->assertOk();
    $reponse->assertDontSee('Outils liés');
});

// ── Cas 2 : outil non publié → n'apparaît pas, même curé ────────────────────────────────────

it('n\'affiche pas un outil curé mais non publié dans l\'annuaire', function () {
    $term = olTerm();
    $publie = olTool(['name' => ['fr_CA' => 'Outil publie visible', 'fr' => 'Outil publie visible'], 'status' => 'published']);
    $nonPublie = olTool(['name' => ['fr_CA' => 'Outil non publie invisible', 'fr' => 'Outil non publie invisible'], 'status' => 'pending']);
    olLier($term, $publie);
    olLier($term, $nonPublie);

    $reponse = $this->get('/glossaire/'.$term->slug);

    $reponse->assertOk();
    $reponse->assertSee('Outil publie visible');
    $reponse->assertDontSee('Outil non publie invisible');
});

// ── Cas 3 : outil archivé → n'apparaît pas, même publié et curé ─────────────────────────────

it('n\'affiche pas un outil curé et publié mais archivé (lifecycle_status)', function () {
    $term = olTerm();
    $actif = olTool(['name' => ['fr_CA' => 'Outil actif visible', 'fr' => 'Outil actif visible']]);
    $archive = olTool([
        'name' => ['fr_CA' => 'Outil archive invisible', 'fr' => 'Outil archive invisible'],
        'lifecycle_status' => 'archived',
    ]);
    olLier($term, $actif);
    olLier($term, $archive);

    $reponse = $this->get('/glossaire/'.$term->slug);

    $reponse->assertOk();
    $reponse->assertSee('Outil actif visible');
    $reponse->assertDontSee('Outil archive invisible');
});
