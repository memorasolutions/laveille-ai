<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2436, volet « clés de dédoublonnage » (2026-09-11), étape 3.
 *
 * L'architecture VACCIN/DOUBLON (voir CommunityController::storeResource et
 * Modules/Directory/app/Console/ModerateTutorialsCommand.php) repose entièrement sur une
 * hypothèse : AUCUNE voie d'insertion de ToolResource ne crée jamais de ligne à
 * is_approved=false. Si l'hypothèse cessait d'être vraie (ex. une approbation asynchrone
 * future qui insère une ligne « en attente »), cette ligne EN ATTENTE serait confondue avec
 * une ligne DÉSAPPROUVÉE PAR LA MODÉRATION - le vaccin bloquerait alors à tort une ressource
 * qui n'a jamais été jugée, pour TOUS les outils.
 *
 * Ce test rend l'hypothèse VÉRIFIÉE plutôt que supposée, sur les 5 voies de production
 * recensées par grep exhaustif (2026-09-11) : `grep -rln "ToolResource::create" --include=*.php`
 * sur tout le dépôt (hors vendor/tests) :
 *   1. CommunityController::storeResource()      - soumission communautaire (HTTP)
 *   2. EnrichTutorialsCommand::handle()           - cron YouTube Data API
 *   3. EnrichFormationsCommand::handle()          - cron OpenRouter/sonar-pro
 *   4. EnrichTutorialsSonarCommand::handle()      - cron oEmbed
 *   5. ImportYoutubeResourcesCommand::handle()    - import JSON manuel (--file=)
 *
 * Toute NOUVELLE voie d'insertion découverte par un futur grep doit être ajoutée à cette
 * énumération - c'est la limite honnête d'un test qui protège des voies CONNUES, pas d'une
 * voie qui n'existe pas encore.
 *
 * Méthode à deux niveaux :
 *   - Voie #1 (communautaire) : preuve COMPORTEMENTALE de bout en bout (requête HTTP réelle,
 *     ligne réellement insérée en base, is_approved lu depuis la base).
 *   - Voies #2 à #5 (crons) : preuve STATIQUE sur le code source. Choix délibéré plutôt qu'une
 *     preuve comportementale : les 4 commandes appellent chacune un service externe différent
 *     (YouTube Data API v3, OpenRouter sonar-pro, oEmbed YouTube) instancié en dur (`new
 *     XxxService`, jamais résolu via le conteneur), donc non substituable par un mock/fake sans
 *     changer leur code de production. Simuler les 3 API pour ce seul test ajouterait de la
 *     fragilité (formats JSON externes recopiés à la main) sans mieux protéger l'invariant
 *     réel : ce qui compte ici n'est pas CE QUE l'API renvoie, mais la valeur d'is_approved au
 *     moment de l'insertion, qui est un littéral fixe (`true`) indépendant de la réponse API.
 *     La preuve porte donc directement sur ce littéral.
 *
 * CONTRE-ÉPREUVE (exigée par le ticket) : en modifiant temporairement un seul des 5 littéraux
 * `true` en `false` (ou, pour la voie #5, en réintroduisant `$config['is_approved']`), CHACUN
 * des tests dont le nom porte son numéro devient rouge. Preuve collée dans le rapport de
 * livraison, pas dans ce fichier.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolResource;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config(['app.locale' => 'fr_CA']);
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
});

/** Lit le contenu source d'un fichier de commande du module Directory. */
function readDirectoryConsoleSource(string $filename): string
{
    $path = base_path("Modules/Directory/app/Console/{$filename}");
    expect(file_exists($path))->toBeTrue("Fichier introuvable, l'invariant ne peut plus être vérifié : {$path}");

    return (string) file_get_contents($path);
}

/**
 * Extrait la valeur associée à la clé 'is_approved' dans le PREMIER appel
 * ToolResource::create([...]) trouvé dans $source, ou null si absent.
 */
function extractIsApprovedLiteral(string $source): ?string
{
    if (! preg_match('/ToolResource::create\(\s*\[(.*?)\]\s*\)\s*;/s', $source, $callMatch)) {
        return null;
    }

    if (! preg_match("/'is_approved'\s*=>\s*([^,\n]+),?/", $callMatch[1], $valueMatch)) {
        return null;
    }

    return trim($valueMatch[1]);
}

// --- Voie #1 : CommunityController::storeResource (HTTP, comportemental) ------------------

test('voie #1 (communautaire) : storeResource ne crée jamais une ressource désapprouvée', function () {
    $tool = new Tool();
    $tool->url = 'https://approval-invariant-test.example';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->is_featured = false;
    $tool->setTranslation('name', 'fr_CA', 'Outil Invariant Approbation');
    $tool->setTranslation('slug', 'fr_CA', 'approval-invariant-test-'.uniqid());
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->save();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(
        route('directory.resources.store', $tool->getTranslation('slug', 'fr_CA')),
        [
            'url' => 'https://blog.example/invariant-approbation',
            'title' => 'Ressource de test invariant',
            'type' => 'article',
            'language' => 'fr',
        ]
    );

    $response->assertOk();

    $resource = ToolResource::where('directory_tool_id', $tool->id)->firstOrFail();

    // L'assertion qui protège l'hypothèse : jamais false à l'insertion.
    expect($resource->is_approved)->toBeTrue();
});

// --- Voies #2 à #5 (crons) : preuve statique sur le littéral is_approved ------------------

test('voie #2 (EnrichTutorialsCommand) : is_approved codé en dur à true, jamais une variable', function () {
    $source = readDirectoryConsoleSource('EnrichTutorialsCommand.php');

    expect(extractIsApprovedLiteral($source))->toBe('true');
});

test('voie #3 (EnrichFormationsCommand) : is_approved codé en dur à true, jamais une variable', function () {
    $source = readDirectoryConsoleSource('EnrichFormationsCommand.php');

    expect(extractIsApprovedLiteral($source))->toBe('true');
});

test('voie #4 (EnrichTutorialsSonarCommand) : is_approved codé en dur à true, jamais une variable', function () {
    $source = readDirectoryConsoleSource('EnrichTutorialsSonarCommand.php');

    expect(extractIsApprovedLiteral($source))->toBe('true');
});

test('voie #5 (ImportYoutubeResourcesCommand) : is_approved fixé à true, plus configurable par le JSON --file=', function () {
    $source = readDirectoryConsoleSource('ImportYoutubeResourcesCommand.php');

    // Cette voie passe '$isApproved' (une variable) à ToolResource::create(), pas le littéral
    // directement - il faut donc vérifier l'AFFECTATION de la variable, pas l'appel lui-même.
    expect(extractIsApprovedLiteral($source))->toBe('$isApproved');

    // La voie était la SEULE des 5 à lire is_approved depuis une source externe
    // ($config['is_approved'] ?? true) - donc théoriquement dégradable à false par un JSON
    // d'import, en confusion directe avec le VACCIN. Corrigé dans ce ticket (2026-09-11) :
    // $isApproved doit être affecté au littéral true, sans condition, et la clé de config ne
    // doit plus être lue nulle part.
    expect($source)->toMatch('/\$isApproved\s*=\s*true\s*;/');
    expect($source)->not->toContain("config['is_approved']");
});
