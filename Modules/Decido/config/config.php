<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

return [
    'name' => 'Decido',

    /*
    |--------------------------------------------------------------------------
    | Gate « EN CONSTRUCTION » — sondages collectifs (/decido)
    |--------------------------------------------------------------------------
    | Tant que true, seul le superadmin voit les pages publiques du module ;
    | tout le reste (anonyme et connecté) reçoit une page 503 sobre. Pattern
    | identique à Academy/Books (voir config('academy.under_construction')).
    | Désactiver via DECIDO_UNDER_CONSTRUCTION=false dans le .env pour ouvrir
    | Décido au public.
    */
    'under_construction' => env('DECIDO_UNDER_CONSTRUCTION', true),

    /*
    |--------------------------------------------------------------------------
    | Politique de rétention / purge des sondages (2026-07-19)
    |--------------------------------------------------------------------------
    | Remplace l'ancienne clé unique 'expiration_months_after_close' (6 mois post-clôture,
    | seul moment où expires_at était écrit) : désormais TOUT sondage a une date d'expiration
    | dès sa création (voir Modules\Decido\Services\PollExpirationService), recalculée à la
    | clôture puis prolongeable manuellement par le créateur. Recherche pp_search + validation
    | Codex/Gemini, approuvée par l'utilisateur - ne pas rouvrir sans demande explicite.
    */

    // Sondage de type "date" : dernière date candidate proposée + N mois.
    'expiration_months_date_type' => 2,

    // Sondage "classique" (options textuelles) ou brouillon (sans créneau généré) : date de
    // création + N mois.
    'expiration_months_classic_or_draft' => 3,

    // Clôture manuelle par le créateur : clôture + N jours (remplace les 6 mois de l'ancienne
    // règle - un sondage clôturé n'a plus besoin d'être conservé aussi longtemps).
    'expiration_days_after_close' => 30,

    // Prolongation manuelle ("Prolonger de 3 mois") : ajoutés à l'expires_at courant, jusqu'au
    // plafond ci-dessous.
    'extension_months' => 3,
    'max_extensions' => 2,

    // Avertissement courriel UNIQUE (pas de cascade J-30/J-7) envoyé quand expires_at tombe dans
    // cette fenêtre (decido:warn-expiring-polls, idempotent via expiry_warned_at).
    'expiry_warning_days_before' => 14,
];
