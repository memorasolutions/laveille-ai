<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2524, étape 3 bis : remplit les cinq valeurs (et SEULEMENT ces cinq, voir la migration
 * 2026_09_13_040000_add_disambiguation_note_to_dictionary_terms) du champ « À ne pas confondre
 * avec » (`disambiguation_note`), sur les fiches dont la mesure du 2026-09-11 a montré qu'un
 * homonyme externe écrase le terme du glossaire dans les résultats de recherche vidéo.
 *
 * IDEMPOTENT, sans écraser une valeur humaine : pour chaque terme,
 * - une colonne vide (null ou '') reçoit le texte attendu ;
 * - une colonne qui contient DÉJÀ exactement le texte attendu ne fait rien (relancer le seeder
 *   ne duplique rien, ne touche pas updated_at inutilement) ;
 * - une colonne qui contient AUTRE CHOSE (un humain a modifié le texte dans l'admin entre-temps)
 *   n'est JAMAIS écrasée - le seeder le signale et passe au terme suivant.
 * Un slug introuvable en base locale (désynchronisée de la production, partielle par nature) est
 * journalisé, jamais fatal.
 */

namespace Modules\Dictionary\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Dictionary\Models\Term;

class DisambiguationNoteSeeder extends Seeder
{
    /**
     * Les cinq cas mesurés le 2026-09-11 (voir tête de la migration ci-dessus pour les taux de
     * faux). Slug fr_CA/fr tel que réellement présent en base - VÉRIFIÉ le 2026-09-13 par requête
     * directe, pas deviné : le slug de « Perplexité (perplexity) » est `perplexite-metrique`, pas
     * `perplexite`.
     */
    private const NOTES = [
        'hub' => 'Le « Nether Hub » de Minecraft, Azure Event Hub et HubSpot portent le même mot sans rapport avec ce concept.',
        'perplexite-metrique' => "Perplexity AI, le moteur de recherche, est un produit commercial : il n'a aucun lien avec cette mesure.",
        'epoque' => 'Le jeu vidéo « Last Epoch » occupe le même mot, sans rapport avec l\'entraînement d\'un modèle.',
        'socket' => "Socket.io est une bibliothèque JavaScript précise, pas le point de connexion réseau décrit ici.",
        'batch' => 'En cuisine et en industrie, un « batch » désigne une fournée, pas un lot de données d\'entraînement.',
    ];

    public function run(): void
    {
        $posed = 0;
        $unchanged = 0;
        $skippedHumanEdit = 0;
        $notFound = 0;

        foreach (self::NOTES as $slug => $note) {
            $term = Term::query()
                ->where('slug->fr_CA', $slug)
                ->orWhere('slug->fr', $slug)
                ->first();

            if (! $term) {
                echo "[DisambiguationNoteSeeder] Terme introuvable pour le slug « {$slug} » - ignoré.\n";
                $notFound++;

                continue;
            }

            $current = $term->disambiguation_note;

            if ($current === $note) {
                $unchanged++;

                continue;
            }

            if ($current !== null && trim((string) $current) !== '') {
                echo "[DisambiguationNoteSeeder] Le terme « {$slug} » a déjà une valeur différente ".
                    "(probablement modifiée à la main) - NON écrasée.\n";
                $skippedHumanEdit++;

                continue;
            }

            $term->disambiguation_note = $note;
            $term->save();
            $posed++;
        }

        echo "[DisambiguationNoteSeeder] {$posed} posée(s), {$unchanged} déjà à jour, ".
            "{$skippedHumanEdit} conservée(s) (édition humaine), {$notFound} introuvable(s).\n";
    }
}
