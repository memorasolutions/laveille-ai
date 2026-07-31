<?php

declare(strict_types=1);

/**
 * Amorçage de la suite de tests.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

// Résout la course entre workers Paratest sur le cache des vues compilées : les 16 workers
// partagent storage/framework/views et compilent simultanément la même vue Blade, ce qui produit
// un fichier PHP tronqué et des erreurs 500 « syntax error » aléatoires. Le bruit est trompeur :
// il imite exactement une vraie ParseError sur le fichier visé, donc un faux positif y devient
// indiscernable d'une régression réelle. Chaque worker compile désormais dans son propre dossier.

$token = getenv('TEST_TOKEN');

if ($token !== false && $token !== '') {
    // Assainit le jeton avant de le mettre dans un chemin : une valeur inattendue ne doit jamais
    // pouvoir produire une traversée de répertoire.
    $token = preg_replace('/[^a-zA-Z0-9]/', '', $token);

    if ($token !== '') {
        $viewsPath = __DIR__.'/../storage/framework/views/paratest-'.$token;

        // Idiome anti-course : deux workers peuvent créer le dossier parent au même instant, donc
        // un mkdir peut échouer légitimement parce que l'autre vient de réussir. On ne traite comme
        // une erreur que le cas où le dossier n'existe toujours pas après la tentative.
        if (! is_dir($viewsPath) && ! @mkdir($viewsPath, 0775, true) && ! is_dir($viewsPath)) {
            throw new RuntimeException('Impossible de créer le cache de vues du worker : '.$viewsPath);
        }

        // Les trois canaux : Laravel lit env() différemment selon la configuration en place.
        putenv("VIEW_COMPILED_PATH=$viewsPath");
        $_ENV['VIEW_COMPILED_PATH'] = $viewsPath;
        $_SERVER['VIEW_COMPILED_PATH'] = $viewsPath;
    }
}

require __DIR__.'/../vendor/autoload.php';
