<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Non-régression de l'incident du 2026-09-11 : dans
 * Modules/Directory/resources/views/public/index.blade.php, un COMMENTAIRE JavaScript logé à
 * l'intérieur d'un attribut x-data="{ ... }" MULTI-LIGNES contenait
 * data-refresh-expired="auto" avec des guillemets DOUBLES. Le navigateur ne lit pas le
 * Javascript : il ferme l'attribut HTML au premier guillemet double rencontré, où qu'il soit
 * (commentaire, chaîne, peu importe). L'expression Alpine se retrouvait tronquée à 4670
 * caractères sur 6896, ce qui provoquait une SyntaxError et empêchait l'initialisation du
 * composant ENTIER - 50 ReferenceError en production et le formulaire de soumission d'outil
 * mort. Ce test parcourt tous les gabarits Blade de Modules/ et themes/ et refuse tout
 * guillemet double logé entre l'ouverture x-data="{ et la fermeture }" d'un objet Alpine
 * multi-lignes.
 */

uses(Tests\TestCase::class);

/**
 * Liste tous les fichiers *.blade.php sous Modules/ et themes/ (racine du dépôt). themes/
 * n'existe pas dans ce dépôt (seul .themes/, un gabarit tiers vendu, en dehors du périmètre) :
 * le glob renvoie alors simplement un ensemble vide pour cette racine, sans erreur.
 *
 * @return array<int, string>
 */
function directoryListerFichiersBladeACouvrir(): array
{
    $base = base_path();
    $racines = [
        $base.'/Modules',
        $base.'/themes',
    ];

    $fichiers = [];

    foreach ($racines as $racine) {
        if (! is_dir($racine)) {
            continue;
        }

        $iterateur = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($racine, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterateur as $fichier) {
            if ($fichier->isFile() && str_ends_with($fichier->getFilename(), '.blade.php')) {
                $fichiers[] = $fichier->getPathname();
            }
        }
    }

    sort($fichiers);

    return $fichiers;
}

/**
 * Retire d'une ligne tout ce qui est du Blade/PHP source (compilé et évalué CÔTÉ SERVEUR,
 * donc jamais transmis tel quel au navigateur) : commentaires Blade {{-- --}}, échos bruts
 * {!! !!} et échos échappés {{ ... }}. Un guillemet double utilisé DANS ces constructions
 * (ex. {{ __("Texte") }}) est une simple délimitation d'argument PHP - le navigateur ne le
 * voit jamais, contrairement au guillemet du bug d'origine, posé en texte brut (commentaire
 * JS) directement dans l'attribut HTML.
 */
function directoryDepouillerExpressionsBlade(string $ligne): string
{
    $ligne = preg_replace('/\{\{--.*?--\}\}/', '', $ligne) ?? $ligne;
    $ligne = preg_replace('/\{!!.*?!!\}/', '', $ligne) ?? $ligne;
    $ligne = preg_replace('/\{\{.*?\}\}/', '', $ligne) ?? $ligne;

    return $ligne;
}

/**
 * Repère, dans le contenu d'un fichier Blade, chaque guillemet double logé entre l'ouverture
 * et la fermeture d'un attribut x-data="{ ... }" MULTI-LIGNES.
 *
 * Un bloc multi-lignes s'ouvre sur une ligne qui se termine par x-data="{ (rien d'autre après
 * l'accolade : c'est ce qui le distingue d'un x-data="{ ... }" tenant sur une seule ligne,
 * hors périmètre ici) et se ferme sur la première ligne suivante dont le contenu, indentation
 * retirée, commence par }". Le guillemet d'ouverture et celui de fermeture sont légitimes (ce
 * sont eux qui délimitent l'attribut HTML) : seuls les guillemets doubles trouvés STRICTEMENT
 * ENTRE les deux, et en dehors de toute construction Blade, comptent comme violation.
 *
 * Découpage volontairement fait sur "\n" (jamais \R/preg_split) : \R, en mode octets (sans
 * modificateur /u), traite l'octet de continuation 0x85 d'un caractère UTF-8 multioctets
 * (ex. l'émoji ✅ = E2 9C 85) comme un saut de ligne NEL - mesuré sur ce dépôt, ça décalait la
 * numérotation de 7 lignes dès le premier tel caractère rencontré. explode("\n", ...) ne coupe
 * que sur l'octet 0x0A réel, exactement ce que compte un éditeur de texte ou `grep -n`.
 *
 * @return array<int, array{ligne_ouverture:int, ligne_fautive:int, extrait:string}>
 */
function directoryTrouverGuillemetsDansXDataMultilignes(string $contenu): array
{
    $lignes = explode("\n", $contenu);

    $violations = [];
    $ligneOuverture = null;

    foreach ($lignes as $index => $ligne) {
        $numero = $index + 1;

        if ($ligneOuverture === null) {
            if (preg_match('/x-data="\{\s*$/', $ligne) === 1) {
                $ligneOuverture = $numero;
            }

            continue;
        }

        // Ligne de fermeture du bloc : referme l'attribut, on ne scrute plus ensuite (ce qui
        // suit }" appartient à un autre attribut).
        if (preg_match('/^\s*\}"/', $ligne) === 1) {
            $ligneOuverture = null;

            continue;
        }

        if (str_contains(directoryDepouillerExpressionsBlade($ligne), '"')) {
            $violations[] = [
                'ligne_ouverture' => $ligneOuverture,
                'ligne_fautive' => $numero,
                'extrait' => trim($ligne),
            ];
        }
    }

    return $violations;
}

it('ne laisse jamais un guillemet double se glisser entre l\'ouverture et la fermeture d\'un objet Alpine x-data multi-lignes', function () {
    $fichiers = directoryListerFichiersBladeACouvrir();

    // Garde-fou : si le glob se met un jour à ne plus rien trouver (racine déplacée, extension
    // renommée...), ce test doit échouer au lieu de passer vide en silence.
    expect($fichiers)->not->toBeEmpty();

    $rapport = [];

    foreach ($fichiers as $chemin) {
        $contenu = file_get_contents($chemin);
        if ($contenu === false) {
            continue;
        }

        foreach (directoryTrouverGuillemetsDansXDataMultilignes($contenu) as $violation) {
            $rapport[] = sprintf(
                '%s:%d (bloc x-data ouvert à la ligne %d) -> %s',
                $chemin,
                $violation['ligne_fautive'],
                $violation['ligne_ouverture'],
                $violation['extrait']
            );
        }
    }

    expect($rapport)->toBe([], "Guillemet double trouvé à l'intérieur d'un attribut Alpine x-data=\"{ ... }\" multi-lignes : ".
        "le navigateur ferme l'attribut HTML au premier guillemet double rencontré et casse l'initialisation du composant entier.\n".
        implode("\n", $rapport));
});
