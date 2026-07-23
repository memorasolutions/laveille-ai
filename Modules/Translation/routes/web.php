<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

// Scaffold nwidart jamais implémenté (TranslationController vide, aucune méthode) - route
// supprimée par cohérence sécurité (2026-07-23), même motif que Modules/Authors (round 6 /100) :
// une resource route sans permission Spatie est une mine terrestre si le contrôleur est un jour
// implémenté sans y penser. La fonctionnalité réelle de traduction vit dans le module Backoffice
// (permission:view_translations/manage_translations, déjà gardée).
