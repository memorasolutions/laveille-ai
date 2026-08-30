<?php

declare(strict_types=1);

/*
 * Rend RÉELLEMENT le blade du constructeur de prompts - le test manquant qui aurait attrapé le 500
 * du 2026-08-20 ($officialTemplates utilisé à l'état vide AVANT sa définition ; Blade rend de haut
 * en bas). On rend la vue DIRECTEMENT avec le seul $tool (exactement comme PublicToolController::show,
 * qui ne passe que $tool - le blade calcule le reste lui-même), pour tester le blade sans la gate
 * « under construction » ni le routage. Deux cas : SANS aucun gabarit (le cas qui plantait) et AVEC.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Database\Seeders\OfficialPromptTemplatesSeeder;
use Modules\Tools\Database\Seeders\ToolSeeder;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ctRenderConstructeur() est déclarée globalement dans tests/Pest.php (pas ici) : Pest le charge
// pour TOUTE exécution quel que soit son périmètre, alors qu'une fonction déclarée dans CE fichier
// ne serait disponible que si ce fichier est chargé avant PromptFromDraftRenderTest.php, qui la
// réutilise - faux dès qu'un fichier est ciblé isolément (corrigé le 2026-08-29).

it('le blade du constructeur se rend SANS erreur quand aucun gabarit officiel n\'existe (cas exact du 500)', function () {
    // Forcer le cas vide : aucun gabarit officiel (c'est ce qui plantait - $officialTemplates
    // etait utilise avant d'etre defini, count() sur variable indefinie -> 500).
    (new ToolSeeder())->run();
    Tool::query()->exists() && \Modules\Tools\Models\SavedPrompt::where('is_official', true)->delete();
    expect(\Modules\Tools\Models\SavedPrompt::where('is_official', true)->count())->toBe(0);

    $tool = Tool::where('slug', 'constructeur-prompts')->firstOrFail();
    $html = view('tools::public.tools.constructeur-prompts', ['tool' => $tool])->render();

    // Le simple fait que render() ne leve PAS d'exception et renvoie du HTML prouve le fix.
    expect(strlen($html))->toBeGreaterThan(1000);
});

it('le blade du constructeur affiche la rangée de gabarits quand ils sont seedés', function () {
    $this->seed(OfficialPromptTemplatesSeeder::class);

    $html = ctRenderConstructeur();

    expect($html)->toContain('ct-gabarit')
        ->and($html)->toContain('Courriel');
});
