<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Composant DRY x-directory::tool-spec-table (spec 2026-08-20) : table HTML sémantique des
 * champs propriétaires réels (underlying_model, is_multimodal, output_types, unique_value,
 * opt_out_training, tutoriels approuvés) jamais rendus ailleurs sur la fiche annuaire.
 * Omission silencieuse LIGNE PAR LIGNE - vérifiée ici champ par champ.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

// Environnement de test = sqlite :memory: (phpunit.xml), qui n'a pas la fonction MySQL FIELD()
// utilisée par PublicDirectoryController::show() pour trier les ressources. Même polyfill que
// Modules/Directory/tests/Feature/AffiliateLinkTest.php et ThinContentNoindexTest.php - scopé à
// ce fichier uniquement.
beforeEach(function () {
    $pdo = DB::connection()->getPdo();
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->sqliteCreateFunction('FIELD', function (...$args) {
            $needle = array_shift($args);
            foreach ($args as $i => $value) {
                if ($needle === $value) {
                    return $i + 1;
                }
            }

            return 0;
        });
    }
});

// Nom distinct de makeNoindexTestTool() (ThinContentNoindexTest.php / AffiliateLinkTest.php) -
// les fonctions Pest sont déclarées en portée globale, un nom dupliqué ferait planter la suite
// complète (fatal « Cannot redeclare »).
function makeSpecTableTestTool(string $slug, array $overrides = []): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Fiche Technique '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test suffisamment longue pour ne pas être considérée comme mince du tout.');
    $tool->setTranslation('short_description', 'fr_CA', 'Un résumé suffisamment long et informatif pour ne pas être considéré comme mince.');
    $tool->url = 'https://exemple-'.$slug.'.test';
    $tool->pricing = 'free';
    $tool->status = 'published';

    foreach ($overrides as $key => $value) {
        $tool->{$key} = $value;
    }

    $tool->save();
    $tool->refresh();

    return $tool;
}

// ── Omission silencieuse, ligne par ligne ───────────────────────────────────

test('la ligne modèle sous-jacent apparaît quand le champ est rempli', function () {
    $tool = makeSpecTableTestTool('outil-modele-rempli', ['underlying_model' => 'GPT-5.2']);

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertSee('Modèle sous-jacent');
    $response->assertSee('GPT-5.2');
});

test('la ligne modèle sous-jacent est absente quand le champ est vide', function () {
    $tool = makeSpecTableTestTool('outil-modele-vide');

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertDontSee('Modèle sous-jacent');
});

test('la ligne multimodal affiche Oui quand le booléen est explicitement true', function () {
    $tool = makeSpecTableTestTool('outil-multimodal-oui', ['is_multimodal' => true]);

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertSee('Multimodal');
    $response->assertSeeInOrder(['Multimodal', 'Oui']);
});

test('la ligne multimodal affiche Non quand le booléen est explicitement false', function () {
    $tool = makeSpecTableTestTool('outil-multimodal-non', ['is_multimodal' => false]);

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertSee('Multimodal');
});

test('la ligne multimodal est absente quand le champ n’a jamais été renseigné (null, pas un défaut false)', function () {
    $tool = makeSpecTableTestTool('outil-multimodal-jamais-rempli');

    expect($tool->is_multimodal)->toBeNull();

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertDontSee('Multimodal');
});

test('la ligne types de sortie apparaît quand le json est non vide', function () {
    $tool = makeSpecTableTestTool('outil-output-types', ['output_types' => ['texte', 'image']]);

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertSee('Types de sortie');
    $response->assertSee('texte, image');
});

test('la ligne types de sortie est absente quand le json est vide', function () {
    $tool = makeSpecTableTestTool('outil-output-types-vide');

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertDontSee('Types de sortie');
});

test('la ligne ce qui le distingue apparaît quand unique_value est renseigné', function () {
    $tool = makeSpecTableTestTool('outil-unique-value', ['unique_value' => 'Le seul outil à faire ceci nativement.']);

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertSee('Ce qui le distingue');
    $response->assertSee('Le seul outil à faire ceci nativement.');
});

test('la ligne ce qui le distingue est absente quand unique_value est vide', function () {
    $tool = makeSpecTableTestTool('outil-unique-value-vide');

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertDontSee('Ce qui le distingue');
});

test('opt_out_training=yes affiche la ligne exclusion des données d’entraînement', function () {
    $tool = makeSpecTableTestTool('outil-optout-yes', ['opt_out_training' => 'yes']);

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertSee("Exclusion des données d'entraînement");
});

test('opt_out_training=no affiche la ligne exclusion des données d’entraînement', function () {
    $tool = makeSpecTableTestTool('outil-optout-no', ['opt_out_training' => 'no']);

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertSee("Exclusion des données d'entraînement");
});

test('opt_out_training=unknown (défaut, bruit sur 500/507 outils) n’affiche jamais la ligne', function () {
    $tool = makeSpecTableTestTool('outil-optout-unknown');

    expect($tool->opt_out_training)->toBe('unknown');

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertDontSee("Exclusion des données d'entraînement");
});

test('la ligne tutoriels disponibles apparaît seulement si au moins un tutoriel est approuvé', function () {
    $tool = makeSpecTableTestTool('outil-tuto-approuve');
    $author = User::factory()->create();

    DB::table('directory_resources')->insert([
        'user_id' => $author->id,
        'directory_tool_id' => $tool->id,
        'title' => 'Tutoriel de test',
        'url' => 'https://exemple.test/tuto',
        'type' => 'article',
        'language' => 'fr',
        'is_approved' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertSee('Tutoriels disponibles');
});

test('la ligne tutoriels disponibles est absente sans tutoriel approuvé', function () {
    $tool = makeSpecTableTestTool('outil-sans-tuto');

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertDontSee('Tutoriels disponibles');
});

// ── Composant isolé : rien à afficher = rien de rendu ───────────────────────

test('le composant tool-spec-table rend une chaîne vide quand aucun des champs n’est renseigné', function () {
    $tool = makeSpecTableTestTool('outil-sans-aucune-spec');

    $html = (string) view('directory::components.tool-spec-table', ['tool' => $tool])->render();

    expect(trim($html))->toBe('');
});

test('le composant tool-spec-table rend une table sémantique quand au moins un champ est renseigné', function () {
    $tool = makeSpecTableTestTool('outil-avec-une-spec', ['underlying_model' => 'Claude']);

    $html = (string) view('directory::components.tool-spec-table', ['tool' => $tool])->render();

    expect($html)->toContain('<table')
        ->and($html)->toContain('<caption')
        ->and($html)->toContain('scope="row"')
        ->and($html)->toContain('Fiche technique');
});

// ── Non-régression : champs déjà rendus ailleurs restent intacts ────────────

test('la fiche show rend toujours 200 et les champs déjà affichés (pricing, type, lancé en) restent intacts', function () {
    $tool = makeSpecTableTestTool('outil-non-regression', [
        'website_type' => 'saas',
        'launch_year' => 2024,
    ]);

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertSee('Tarification');
    $response->assertSee('Saas');
    $response->assertSee('2024');
});

test('launch_year n’est jamais dupliqué par le composant tool-spec-table (déjà rendu ailleurs)', function () {
    $tool = makeSpecTableTestTool('outil-launch-year-pas-de-doublon', ['launch_year' => 2023]);

    $html = (string) view('directory::components.tool-spec-table', ['tool' => $tool])->render();

    expect($html)->not->toContain('Lancé en');
});
