<?php

declare(strict_types=1);

use Tests\TestCase;

uses(Tests\TestCase::class);

// Round 73 (2026-07-27) : passe adversariale fraîche, périmètre transitif approfondi - le
// subagent a drillé dans un ENFANT (Modules/FrontTheme/resources/views/components/
// newsletter-form.blade.php) d'un fichier PARENT déjà "couvert" à un round antérieur
// (tools-newsletter-cta.blade.php, rendu sur constructeur-prompts.blade.php:694 via
// @include('fronttheme::partials.tools-newsletter-cta', ...)). Manque trouvé : 4 chaînes FR
// en dur passées par __() dans newsletter-form.blade.php (lignes 32, 33, 38, 47) jamais
// ajoutées à lang/en.json - 'Votre adresse courriel', 'Votre courriel', 'S’inscrire' (note :
// apostrophe courbe U+2019, PAS une apostrophe droite), 'Double opt-in. Loi 25 / RGPD.
// Désabonnement 1-clic.'. Leçon méthodologique (8e round consécutif à trouver ce type de
// manque) : même un fichier "déjà audité" peut cacher un enfant jamais lui-même drillé.

it('has English translations for the newsletter-form component strings (round 73)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $keys = [
        'Votre adresse courriel',
        'Votre courriel',
        'S’inscrire',
        'Double opt-in. Loi 25 / RGPD. Désabonnement 1-clic.',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($en[$key])->not->toBe($key);
    }
});

it('translates the newsletter-form component strings to English via app locale (round 73)', function () {
    app()->setLocale('en');

    expect(__('Votre adresse courriel'))->toBe('Your email address');
    expect(__('Votre courriel'))->toBe('Your email');
    expect(__('S’inscrire'))->toBe('Subscribe');
    expect(__('Double opt-in. Loi 25 / RGPD. Désabonnement 1-clic.'))
        ->toBe('Double opt-in. Quebec Law 25 / GDPR compliant. One-click unsubscribe.');
});
