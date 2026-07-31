<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 135 (2026-07-30) : la liste des outils contredisait la page de l'outil.
//
// La migration #325 a introduit `construction_mode` pour distinguer deux situations que son propre
// docblock oppose explicitement : un outil JAMAIS lancé (« construction ») et un outil RETIRÉ
// TEMPORAIREMENT après avoir été public (« revision », cas du constructeur de prompts).
//
// La page dédiée honore bien la distinction (under-construction.blade.php branche sur le mode et
// affiche « Mise à jour en cours » + la bannière « Vos prompts déjà sauvegardés sont intacts »).
// Mais la carte de /outils ne transportait même pas le champ : son payload n'avait que le booléen,
// et son badge affichait « 🚧 Bientôt » en dur.
//
// Conséquence : sur toutes les autres cartes du site, « Bientôt » veut dire « jamais lancé ». Un
// utilisateur qui avait déjà sauvegardé des prompts et qui revient par /outils - le point d'entrée
// naturel - lisait donc que l'outil est à venir, et pouvait conclure que son travail avait disparu.
// C'est exactement le public que le mode « revision » a été créé pour rassurer, et le point de
// contact le plus visible disait le contraire, avant même qu'il puisse cliquer.
//
// Correctif : le mode voyage jusqu'à la carte, et le badge reprend MOT POUR MOT le libellé de la
// page dédiée, pour que l'utilisateur lise la même chose aux deux endroits.

it('carries the construction mode to the tool card (round 135)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/index.blade.php'));

    expect($blade)->toContain("'construction_mode' => \$t->construction_mode ?? 'construction',");
});

it('shows the revision wording instead of the never-launched one (round 135)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/index.blade.php'));

    // Le libellé est repris de la page dédiée : même vocabulaire des deux côtés.
    expect($blade)->toContain("tool.construction_mode === 'revision' ? '✨ {{ __('Mise à jour en cours') }}' : '🚧 {{ __('Bientôt') }}'");
    // Le texte n'est plus figé dans le corps de la balise.
    expect($blade)->not->toContain('x-cloak>🚧 {{ __(\'Bientôt\') }}</span>');
});

it('keeps a visually distinct badge for each mode (round 135)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/index.blade.php'));

    expect($blade)->toContain("tool.construction_mode === 'revision' ? 'tools-badge tools-badge-revision' : 'tools-badge tools-badge-construction'");
    expect($blade)->toContain('.tools-badge-revision { background: #3730A3; color: #fff; }');
    // La teinte « Bientôt » d'origine reste inchangée.
    expect($blade)->toContain('.tools-badge-construction { background: var(--c-accent, #9A2A06); color: #fff; }');
});

it('defaults to construction when the mode is absent (round 135)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/index.blade.php'));

    // Un outil sans mode (données anciennes) doit garder le comportement historique, pas basculer
    // par accident en « Mise à jour en cours ».
    expect($blade)->toContain("?? 'construction',");
});

it('renders the tools list with a tool in revision mode (round 135, real page)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => true,
        'construction_mode' => 'revision',
        'category' => 'productivite',
    ]);

    $this->get('/outils')->assertOk();
});
