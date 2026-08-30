<?php

declare(strict_types=1);

/**
 * #P0-audit 2026-08-30 (audit DRY overnight v1.237.1-v1.238.3, point 3) : translateBatch()
 * retirait déjà le tiret cadratin (—, U+2014) d'un titre traduit AVANT ce correctif, mais via sa
 * PROPRE copie de `str_replace('—', '-', $sansNumero)` - une TROISIÈME implémentation de la même
 * règle CLAUDE.md #10, à côté de lv_strip_em_dash() (app/Helpers/typo.php, la fonction DÉDIÉE du
 * projet pour cette règle précise) et de son usage dans NewsImageService::generateFallbackImage()
 * (v1.237.5). Les deux copies faisaient EXACTEMENT la même chose (même substitution caractère
 * pour caractère) - le risque n'est pas dans le comportement actuel, identique avant/après, mais
 * dans l'évolution future : une correction apportée à lv_strip_em_dash() (ex. gérer aussi le
 * tiret demi-cadratin, ou un cas limite de citation) n'aurait jamais atteint cette copie oubliée.
 * Ce test verrouille le RÉSULTAT (comportement inchangé), pas l'implémentation.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Core\Services\TranslationService;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('retire le tiret cadratin d\'un titre traduit par lot, comme avant ce correctif', function () {
    config()->set('services.openrouter.api_key', 'cle-de-test');

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [[
                'message' => ['content' => "1. Le firmware — désormais signé — se déploie seul"],
            ]],
        ], 200),
    ]);

    $resultat = TranslationService::translateBatch(['The firmware, now signed, deploys alone '.uniqid()]);

    expect($resultat['statut'])->toBe('ok')
        ->and($resultat['titres'][0])->toBe('Le firmware - désormais signé - se déploie seul')
        ->and($resultat['titres'][0])->not->toContain("\u{2014}");
});
