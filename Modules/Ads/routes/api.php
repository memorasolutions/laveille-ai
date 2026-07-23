<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

// Scaffold nwidart jamais implémenté (AdsController vide, aucune méthode d'écriture réelle) -
// route API supprimée par cohérence sécurité (2026-07-23), même motif que Export/Translation/
// Backup (v1.117.23) : une apiResource sans permission Spatie est une mine terrestre si le
// contrôleur est un jour implémenté sans y penser.
