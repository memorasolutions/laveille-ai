<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

// Scaffold nwidart jamais implémenté (BackupController vide, aucune méthode) - route supprimée
// par cohérence sécurité (2026-07-23), même motif que Modules/Authors (round 6 /100). Module
// désactivé (modules_statuses.json) donc non chargé actuellement, mais corrigé quand même en
// prévention d'une réactivation future sans qu'on y repense. La fonctionnalité réelle de backup
// vit dans le module Backoffice (permission:view_backups/manage_backups, déjà gardée).
