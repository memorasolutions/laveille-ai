<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

// Scaffold nwidart jamais implémenté (RoadmapController vide, aucune méthode d'écriture réelle)
// - route API supprimée par cohérence sécurité (2026-07-23), même motif que Export/Translation/
// Backup (v1.117.23). Le vrai module Roadmap (Modules/Roadmap/routes/web.php) est correctement
// gardé par permission manage_roadmap.
