<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * #P0-audit 2026-08-30 (audit DRY overnight v1.237.1-v1.238.3, point 2) : mêmes défaut et preuve
 * que Modules/Tools/tests/Feature/CalculatriceTaxesContentTest.php ("n'utilise plus input
 * type=number pour les montants..."), appliqués ici. Un <input type="number"> REJETTE la virgule
 * française au clavier - confirmé par frappe réelle sur calculatrice-taxes.blade.php le même jour
 * ("12,50" devient "1250" dans le DOM, une erreur de 100x). Les trois champs monétaires de ce
 * simulateur (revenu brut, cotisation REER, temps supplémentaire) utilisaient exactement le même
 * type="number" + x-model.number, sans aucun parsing défensif - donc le même défaut, jamais mesuré
 * ni corrigé avant ce correctif. Fixé en réutilisant window.CalcParseAmount (extrait de
 * calculatrice-taxes.blade.php vers public/tools/js/calc-parse-amount.js dans le même correctif -
 * DRY, pas une seconde implémentation du même parsing).
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->path = base_path('Modules/Tools/resources/views/public/tools/simulateur-fiscal.blade.php');
    $this->content = file_get_contents($this->path);

    // Même convention que MinuteurVisuelToolTest.php : la route /outils/{slug} résout un Tool en
    // base, absent d'un sqlite :memory: fraîchement migré (RefreshDatabase ne seed pas).
    Tool::firstOrCreate(['slug' => 'simulateur-fiscal'], [
        'name' => 'Simulateur fiscal',
        'description' => 'Simulez vos impôts et visualisez la répartition avec des graphiques.',
        'icon' => '📊',
        'sort_order' => 8,
        'is_active' => true,
        'is_under_construction' => false,
    ]);
});

it('n\'utilise plus input type=number pour le revenu, le REER et le temps supplémentaire', function () {
    expect($this->content)
        ->not->toContain('type="number" class="form-control" x-model.number="income"')
        ->not->toContain('type="number" class="form-control" x-model.number="rrsp"')
        ->not->toContain('type="number" class="form-control" x-model.number="overtime"')
        ->toContain('type="text" inputmode="decimal" autocomplete="off" class="form-control" x-model="incomeText"')
        ->toContain('type="text" inputmode="decimal" autocomplete="off" class="form-control" x-model="rrspText"')
        ->toContain('type="text" inputmode="decimal" autocomplete="off" class="form-control" x-model="overtimeText"');
});

it('charge window.CalcParseAmount partagé plutôt que de reparser le montant à la main', function () {
    expect($this->content)
        ->toContain("asset('tools/js/calc-parse-amount.js')")
        ->toContain('window.CalcParseAmount')
        // Les 3 curseurs (type="range") restent sur x-model.number - immunisés (glisser, jamais
        // taper) - seuls les 3 champs texte changent. Preuve qu'on n'a pas migré la mauvaise cible.
        ->toContain('x-model.number="income"')
        ->toContain('x-model.number="rrsp"')
        ->toContain('x-model.number="overtime"');
});

it('rend la page sans exception PHP avec les nouveaux champs texte', function () {
    // NewsSource/NewsArticle non nécessaires ici : outil autonome, config statique app/serveur.
    $response = $this->get('/outils/simulateur-fiscal');
    $response->assertOk();
    $response->assertSee('x-model="incomeText"', false);
});
