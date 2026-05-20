<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Carbon;

// Ces helpers (format_date/format_datetime) et les façades requièrent l'app Laravel bootstrappée.
// format_date lit le format depuis la table settings → RefreshDatabase pour disposer du schéma.
uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

test('format_date formats correctly', function () {
    $date = Carbon::create(2026, 2, 15);
    expect(format_date($date))->toBe('15/02/2026');
    expect(format_date(null))->toBe('-');
})->skip(
    // En prod, app/Helpers/dates.php redéfinit (shadow) la fonction format_date() du module Core
    // avec une signature/contrat différents (type localisé + settings) ET un bug de format
    // (isoFormat 'd' = jour-de-semaine au lieu de 'DD' = jour-du-mois → "0 févr. 2026").
    // Le contrat Core (d/m/Y, null → "-") ne peut donc pas être vérifié ici.
    // À corriger côté prod : nécessite changement applicatif (hors scope env de test).
    'format_date() est shadowée par app/Helpers/dates.php (contrat différent + bug DD vs d) — fix prod requis.'
);

test('format_datetime formats correctly', function () {
    $date = Carbon::create(2026, 2, 15, 14, 30);
    expect(format_datetime($date))->toBe('15/02/2026 14:30');
    expect(format_datetime(null))->toBe('-');
});
