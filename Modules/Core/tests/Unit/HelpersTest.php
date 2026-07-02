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

// format_date() n'est plus définie deux fois : Modules/Core/app/Helpers/helpers.php a été
// nettoyé de son doublon mort (jamais appelé côté applicatif) et buggé. La version canonique
// vit désormais uniquement dans app/Helpers/dates.php (configurable via Settings).
// Ce test couvre cette version survivante et sert de non-régression pour le bug corrigé :
// isoFormat 'd' = jour-de-semaine (ex. "0" un dimanche) alors que 'D' = jour-du-mois.
test('format_date (app/Helpers/dates.php) formats correctly and no longer uses the buggy day token', function () {
    $date = Carbon::create(2026, 2, 15); // un dimanche

    expect(format_date($date))->toBe('15 févr. 2026');
    expect(format_date(null))->toBe('');
});

test('format_datetime formats correctly', function () {
    $date = Carbon::create(2026, 2, 15, 14, 30);
    expect(format_datetime($date))->toBe('15/02/2026 14:30');
    expect(format_datetime(null))->toBe('-');
});
