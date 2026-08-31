<?php

declare(strict_types=1);

/**
 * Fichier TEMPORAIRE - preuve adversariale du ticket #2095, retiré dans le commit suivant.
 *
 * But : prouver en conditions réelles que le sas "smoke" bloque effectivement le déploiement -
 * jamais affirmé sans démonstration (voir docs/specs/2026-08-31-ci-deploy-gating-decision.md,
 * section "VÉRIFICATION EXIGÉE"). Ce fichier vit dans tests/Unit, donc dans la testsuite
 * "Unit" que le job "smoke" de ci.yml exécute inconditionnellement (--testsuite=Architecture,Unit).
 *
 * Sûr par construction, même si le sas échouait à bloquer : tests/ est explicitement exclu du
 * rsync de deploy.yml (--exclude='tests/'), donc ce fichier ne peut de toute façon jamais
 * atteindre la production, quel que soit le sort du gate.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

it('échoue délibérément pour prouver que le sas smoke bloque le déploiement (ticket #2095)', function () {
    expect(false)->toBeTrue();
});
