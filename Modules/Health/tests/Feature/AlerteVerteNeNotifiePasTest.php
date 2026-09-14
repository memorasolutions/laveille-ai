<?php

declare(strict_types=1);

/**
 * Un contrôle VERT ne doit ni déclencher un courriel, ni être titré comme un problème (#2566).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * DÉFAUT RÉELLEMENT SURVENU, signalé par le fondateur le 2026-09-14 : il a reçu un courriel
 * titré « approche d'une limite et doit être surveillé » dont le corps disait « API ProductHunt
 * fonctionnelle ». Une alerte inventait un problème inexistant.
 *
 * Deux causes, deux contrôles ici :
 *  1. spatie/laravel-health décide d'ENVOYER sur la PRÉSENCE d'un message de notification, jamais
 *     sur le statut (tant que only_on_failure est false). Un `ok($message)` suffisait donc à
 *     poster un courriel à chaque passage réussi.
 *  2. La notification ne connaissait que deux états, alors qu'il en existe trois.
 */

use Spatie\Health\Enums\Status;

uses(Tests\TestCase::class);

it('ne pose AUCUN message de notification quand le contrôle est vert', function () {
    $source = file_get_contents(base_path('Modules/Health/app/Checks/ProductHuntApiCheck.php'));

    // C'est CETTE ligne qui envoyait le courriel. Elle ne doit pas revenir.
    expect($source)->not->toContain('$result->ok($message)');
    expect($source)->toContain('$result->ok()');
});

// Les trois contrôles maison doivent suivre la MÊME convention. Sans ce contrôle, le prochain
// contrôle de santé écrit reproduira exactement la même erreur - ce qui vient d'arriver.
it('impose la convention à TOUS les contrôles de santé maison, pas seulement à celui qui a fauté', function () {
    $fautifs = [];

    foreach (glob(base_path('Modules/Health/app/Checks/*.php')) as $fichier) {
        if (str_ends_with($fichier, '.php') === false || str_contains($fichier, '.bak')) {
            continue;
        }
        $source = file_get_contents($fichier);

        // `->ok(` suivi d'autre chose qu'une parenthèse fermante = un message sur un verdict vert.
        if (preg_match('/->ok\(\s*[^)\s]/', $source)) {
            $fautifs[] = basename($fichier);
        }
    }

    expect($fautifs)->toBe([], 'Ces contrôles posent un message sur un verdict vert, ce qui déclenche un courriel à chaque passage : '.implode(', ', $fautifs));
});

it('titre le courriel RETABLI, jamais AVERTISSEMENT, quand plus rien n\'est en défaut', function () {
    $source = file_get_contents(base_path('Modules/Health/app/Notifications/CheckFailedNotification.php'));

    // L'ancien binaire « urgent ? URGENT : AVERTISSEMENT » est ce qui produisait le faux libellé.
    expect($source)->not->toContain("\$gravity = \$urgent ? 'URGENT' : 'AVERTISSEMENT';");

    expect($source)->toContain("'RETABLI'");
    expect($source)->toContain('$avertissement');
    expect($source)->toContain('est revenu à la normale');
});

it('distingue bien les trois statuts de Spatie (le troisième existe)', function () {
    expect(Status::ok()->value)->toBe('ok');
    expect(Status::warning()->value)->toBe('warning');
    expect(Status::failed()->value)->toBe('failed');
});
