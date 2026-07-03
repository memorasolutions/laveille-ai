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

// Bug SEO confirmé (2026-07-03, /glossaire/modele-frontiere) : Str::limit() coupait en plein
// mot ("...comporte d..."), ce qui décourage le clic dans les SERP Google. safe_excerpt() coupe
// toujours au dernier espace avant la limite.
test('safe_excerpt coupe au dernier espace avant la limite, jamais en plein mot', function () {
    $text = 'La veille technologique est essentielle parce que repousser la limite comporte des risques importants pour toute organisation moderne qui souhaite rester compétitive sur son marché aujourd\'hui.';

    $result = safe_excerpt($text, 160);

    expect($result)->toEndWith('...');
    expect($result)->not->toContain('  ');

    // Le mot juste avant "..." doit être un mot complet du texte original, pas un fragment.
    $withoutEnd = mb_substr($result, 0, -3);
    $lastWord = trim(mb_substr($withoutEnd, mb_strrpos($withoutEnd, ' ') ?: 0));
    expect($text)->toContain($lastWord);
});

test('safe_excerpt ne modifie pas un texte déjà plus court que la limite', function () {
    $text = 'Un texte court.';

    expect(safe_excerpt($text, 160))->toBe($text);
});

test('safe_excerpt ne dépasse jamais limit + strlen(end) caractères', function () {
    $text = str_repeat('mot ', 100); // texte long sans ponctuation
    $end = '...';
    $limit = 160;

    $result = safe_excerpt($text, $limit, $end);

    expect(mb_strlen($result))->toBeLessThanOrEqual($limit + mb_strlen($end));
});

test('safe_excerpt gère null et chaîne vide', function () {
    expect(safe_excerpt(null))->toBe('');
    expect(safe_excerpt(''))->toBe('');
});
