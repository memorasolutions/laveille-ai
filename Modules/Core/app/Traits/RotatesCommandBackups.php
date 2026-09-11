<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Rotation des sauvegardes écrites par une commande Artisan.
 *
 * Plusieurs commandes du dépôt écrivent un instantané JSON AVANT de muter des données
 * (garde-fou « backup avant toute écriture »), mais ne retirent jamais les anciens : le
 * dossier enfle au rythme de l'usage humain, sans aucune planification pour le révéler.
 * Le mécanisme vivait déjà dans Modules\News\app\Console\PruneDraftsCommand ; il est ici
 * extrait pour que les quatre commandes partagent UNE seule règle de suppression, parce
 * qu'une divergence sur un effacement de fichiers est dangereuse par nature.
 */
trait RotatesCommandBackups
{
    /**
     * Conserve les sauvegardes les plus récentes correspondant au motif exact.
     *
     * La borne porte sur le MOTIF exact du nom de fichier et JAMAIS sur un dossier
     * entier, afin de ne jamais emporter un fichier voisin qui partagerait le dossier.
     *
     * Le format horodaté Ymd-His rend le tri lexicographique chronologique : aucun tri
     * par date de modification n'est requis.
     *
     * @param  string  $globPattern  Motif glob exact des noms de sauvegarde concernés.
     * @param  int  $keep  Nombre maximal de sauvegardes à conserver, au minimum 1.
     * @return int Nombre de fichiers effectivement supprimés.
     *
     * @throws InvalidArgumentException Si la borne de conservation est inférieure à 1.
     */
    protected function rotateCommandBackups(string $globPattern, int $keep = 14): int
    {
        if ($keep < 1) {
            throw new InvalidArgumentException(
                'Le nombre de sauvegardes à conserver doit être supérieur ou égal à 1.'
            );
        }

        $files = glob($globPattern) ?: [];

        sort($files);

        $excess = count($files) - $keep;

        if ($excess <= 0) {
            return 0;
        }

        $deleted = 0;

        for ($index = 0; $index < $excess; $index++) {
            if (File::delete($files[$index])) {
                $deleted++;
            }
        }

        return $deleted;
    }
}
