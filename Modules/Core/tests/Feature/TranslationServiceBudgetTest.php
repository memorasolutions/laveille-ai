<?php

declare(strict_types=1);

/**
 * Budget explicite de TranslationService::translateBatch() (paramètre $budgetSecondes, ajouté le
 * 2026-08-24, mesure en production) - preuve que :
 *
 *  - en son ABSENCE, la valeur de config (services.openrouter.translation_budget_seconds)
 *    s'applique toujours, EXACTEMENT comme avant l'ajout du paramètre ;
 *  - quand il est FOURNI, il remplace la config dans le calcul de l'échéance, sans toucher à la
 *    clé de cache, à la garantie de compte de lignes ni à la cascade de modèles.
 *
 * MÉTHODE : le premier modèle de la cascade répond LENTEMENT (délai réel simulé) avec un compte
 * de lignes incohérent (donc rejeté, la boucle passe au modèle suivant) - avec un budget de
 * config volontairement minimal (le plancher de 3 s imposé par translateBatch()), ce délai à lui
 * seul épuise le budget et le SECOND modèle n'est jamais tenté : le lot échoue. Avec un budget
 * explicite de 120 s (celui que passe désormais Modules\News\Console\TranslateTitlesCommand), le
 * même délai laisse largement le temps de tenter le second modèle, qui répond correctement : le
 * lot réussit. Seule la présence du paramètre distingue les deux issues.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Core\Services\TranslationService;

uses(Tests\TestCase::class, RefreshDatabase::class);

/**
 * Fabrique une réponse fausse en deux temps : le premier appel est LENT et rend un compte de
 * lignes incohérent (rejeté par translateBatch, la cascade passe au modèle suivant) ; le second
 * appel est instantané et rend un compte de lignes conforme (accepté).
 */
function tsbFakeLentPuisConforme(float $delaiSecondes): void
{
    Http::fake([
        'openrouter.ai/*' => function () use ($delaiSecondes) {
            static $appel = 0;
            $appel++;

            if ($appel === 1) {
                usleep((int) round($delaiSecondes * 1_000_000));

                return Http::response([
                    'choices' => [['message' => ['content' => "1. Une seule ligne rendue"]]],
                ], 200);
            }

            return Http::response([
                'choices' => [['message' => ['content' => "1. Traduit A\n2. Traduit B"]]],
            ], 200);
        },
    ]);
}

it('utilise la config quand budgetSecondes est absent : un budget minimal (3 s, plancher) fait échouer le lot après un premier modèle lent', function () {
    config()->set('services.openrouter.api_key', 'cle-de-test');
    // Plancher imposé par translateBatch() (max(3, ...)) - même en configurant plus bas, 3 s
    // reste la valeur réellement utilisée. Le premier modèle (lent, 2,5 s) épuise ce budget à lui
    // seul : le second modèle n'est jamais tenté.
    config()->set('services.openrouter.translation_budget_seconds', 1);

    tsbFakeLentPuisConforme(2.5);

    $resultat = TranslationService::translateBatch(['Texte budget config A '.uniqid(), 'Texte budget config B '.uniqid()]);

    expect($resultat['statut'])->toBe('indisponible');
})->group('lent');

it('utilise le budget explicite quand il est fourni : 120 s laisse le temps de tenter le second modèle après le même délai', function () {
    config()->set('services.openrouter.api_key', 'cle-de-test');
    // Même config minimale que le test ci-dessus : si le paramètre n'était PAS réellement
    // utilisé, ce test échouerait exactement comme le précédent.
    config()->set('services.openrouter.translation_budget_seconds', 1);

    tsbFakeLentPuisConforme(2.5);

    $resultat = TranslationService::translateBatch(
        ['Texte budget explicite A '.uniqid(), 'Texte budget explicite B '.uniqid()],
        budgetSecondes: 120,
    );

    expect($resultat['statut'])->toBe('ok')
        ->and($resultat['titres'])->toBe(['Traduit A', 'Traduit B']);
})->group('lent');
