<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION: publie la configuration du journal d'activité, qui n'existait pas dans ce dépôt.
 * RAISON: sans ce fichier, la valeur appliquée était celle du paquet - 365 jours - alors que
 *         le commentaire de routes/console.php annonçait 30 jours. Deux chiffres faux pour le
 *         prix d'un, et aucun des deux n'était le bon.
 *
 * CE QUI SE JOUE ICI, et ce n'est pas un réglage cosmétique : 20 modèles alimentent ce journal
 * depuis le 15 février 2026 (actualités, blogue, glossaire, annuaire, infolettre, réglages...).
 * C'est le registre éditorial sur lequel doit s'appuyer le module d'historique et d'analytique
 * demandé le 2026-09-21. Une purge à 365 jours aurait commencé à effacer cette matière dès le
 * 15 février 2027, silencieusement, un dimanche, sans que rien ne le signale.
 */

return [

    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    /*
     * Rétention portée à 5 ans (au lieu des 365 jours par défaut du paquet).
     *
     * POURQUOI 5 ANS ET NON « POUR TOUJOURS » : une table de journal non purgée grossit sans
     * limite, et ce projet a déjà connu un `pulse_entries` de 1 Go. Cinq ans couvre très
     * largement toute analyse de performance éditoriale utile, tout en gardant une borne.
     * La commande `activitylog:clean` reste planifiée chaque semaine dans routes/console.php -
     * elle ne fait simplement plus rien avant 2031.
     *
     * Si le module d'historique exige un jour une conservation intégrale, c'est ICI que la
     * décision se prend, en une ligne visible dans le dépôt, et non dans un défaut de paquet
     * que personne ne lit.
     */
    'delete_records_older_than_days' => (int) env('ACTIVITY_LOGGER_RETENTION_DAYS', 1825),

    'default_log_name' => 'default',

    'default_auth_driver' => null,

    'subject_returns_soft_deleted_models' => false,

    'activity_model' => \Spatie\Activitylog\Models\Activity::class,

    'table_name' => 'activity_log',

    'database_connection' => env('ACTIVITY_LOGGER_DB_CONNECTION'),
];
