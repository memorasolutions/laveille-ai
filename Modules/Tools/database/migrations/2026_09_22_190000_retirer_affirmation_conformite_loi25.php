<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * Retire l'affirmation de conformite a la Loi 25 la ou elle porte sur NOTRE anonymiseur.
 * L'outil remplace des donnees personnelles par des jetons et garde la table de correspondance
 * chez la personne : c'est une PSEUDONYMISATION, reversible par conception. Au sens de la Loi 25
 * comme du RGPD, une donnee pseudonymisee reste une donnee personnelle. Affirmer la conformite
 * etait donc faux, et le pire est que la page le demontrait elle-meme quelques lignes plus bas.
 *
 * DEUX MIGRATIONS ONT DEJA ECHOUE SUR CE DEFAUT, CHACUNE EN SILENCE. C'est la raison d'etre de
 * la forme de ce fichier, et chaque garde ci-dessous vient d'une mesure, pas d'une precaution.
 *
 * 1. La migration 2026_09_22_170000 ne remplacait la description que si elle valait EXACTEMENT
 *    le texte du seeder d'origine - garde volontaire, pour ne pas ecraser une retouche faite a
 *    la main dans l'ecran d'administration. Mesure : la description de production AVAIT ete
 *    reecrite a la main, et la base locale en portait une TROISIEME variante. La garde a bloque
 *    le correctif dans les deux bases, sans lever la moindre erreur, pendant que le deploiement
 *    etait declare reussi. D'ou le passage a un remplacement de la seule CLAUSE fautive.
 *
 * 2. La reecriture par REPLACE() SQL aurait echoue tout aussi silencieusement : mesure du
 *    2026-09-22, la colonne `faq` ne contient AUCUN accent litteral - le JSON traduisible y est
 *    stocke avec des sequences d'echappement (é pour e accent aigu). Un motif accentue n'y
 *    trouve rien. D'ou le remplacement ci-dessous sur la chaine BRUTE, dans les DEUX
 *    representations possibles.
 *
 * POURQUOI LA CHAINE BRUTE PLUTOT QUE DECODER LE JSON : re-encoder reecrirait tout le champ avec
 * les options de json_encode du moment, ce qui changerait la representation des barres obliques
 * et des accents AILLEURS dans le meme champ - des octets modifies sans aucune raison, invisibles
 * a la relecture. Remplacer dans la chaine brute ne touche que la clause visee.
 *
 * Reversible : down() applique le remplacement inverse, borne de la meme facon.
 */
return new class extends Migration
{
    /**
     * Chaque cible : la table, la colonne, la ligne (par slug), et la clause a remplacer.
     *
     * Le bornage par slug n'est pas cosmetique : sans lui, « conforme a la Loi 25 » serait aussi
     * remplace dans les fiches qui decrivent un outil TIERS reellement conforme - une affirmation
     * legitime, qu'on n'a pas a corriger.
     */
    private const CIBLES = [
        [
            'table' => 'tools',
            'colonne' => 'description',
            'slug' => 'anonymiseur',
            // Servie en meta description et dans le JSON-LD de la page de l'outil.
            'avant' => 'Conforme à la Loi 25 et au RGPD.',
            'apres' => 'Le remplacement est réversible.',
        ],
        [
            'table' => 'dictionary_terms',
            'colonne' => 'faq',
            'slug' => 'anonymisation',
            // ATTENTION : ici `slug` est traduisible (Spatie), donc stocké en JSON
            // {"fr_CA":"anonymisation","fr":"anonymisation"} et jamais en chaîne nue. Voir
            // lignesCibles(), qui ne fait plus confiance à la représentation du slug.
            // Cette reponse se contredisait DANS LA MEME PHRASE : elle expliquait que remplacer
            // un nom releve de la pseudonymisation, « pas de l'anonymisation - la cle de
            // correspondance reste chez vous », puis concluait en vendant un outil « conforme a
            // la Loi 25 ». Servie deux fois : en HTML et dans le JSON-LD FAQPage.
            'avant' => 'anonymisation conforme à la Loi 25',
            'apres' => 'anonymisation local, qui applique exactement ce remplacement réversible',
        ],
    ];

    public function up(): void
    {
        $this->appliquer('avant', 'apres');
    }

    public function down(): void
    {
        $this->appliquer('apres', 'avant');
    }

    private function appliquer(string $de, string $vers): void
    {
        foreach (self::CIBLES as $cible) {
            if (! Schema::hasTable($cible['table'])) {
                continue;
            }

            foreach ($this->lignesCibles($cible) as $ligne) {
                if (! isset($ligne->{$cible['colonne']})) {
                    continue;
                }

                $valeur = (string) $ligne->{$cible['colonne']};
                $corrigee = $this->remplacerDansLesDeuxRepresentations($valeur, $cible[$de], $cible[$vers]);

                if ($corrigee === $valeur) {
                    continue; // deja corrigee, ou clause absente : on ne touche a rien
                }

                // Ecriture par id : la ligne a deja ete identifiee, inutile de rejouer la selection.
                DB::table($cible['table'])
                    ->where('id', $ligne->id)
                    ->update([$cible['colonne'] => $corrigee]);
            }
        }
    }

    /**
     * Rend les lignes a corriger, quelle que soit la representation de leur slug ET quel que soit
     * le moteur de base de donnees.
     *
     * DEUX DEFAUTS SUCCESSIFS ONT PRODUIT CETTE METHODE, tous deux trouves par une passe
     * adversariale APRES que ce fichier ait ete ecrit pour en corriger deux autres.
     *
     * 1. La premiere version faisait `where('slug', 'anonymisation')` sur `dictionary_terms` :
     *    un no-op GARANTI, puisque la colonne y est traduisible et contient du JSON, jamais la
     *    chaine nue. Le meme defaut que celui que ce fichier corrige, reproduit dans le correctif.
     *
     * 2. La deuxieme version le reglait par `JSON_UNQUOTE(JSON_EXTRACT(slug, '$.fr_CA'))`, ce que
     *    fait le seeder du glossaire. Mais les tests et la CI tournent sur SQLite, qui ne CONNAIT
     *    PAS `JSON_UNQUOTE` : mesure du 2026-09-22, « no such function: JSON_UNQUOTE ». La
     *    migration aurait fait echouer la suite entiere, donc bloque le deploiement - un correctif
     *    qui casse la livraison de tous les autres.
     *
     * D'ou le choix present : aucun filtrage JSON cote SQL. On ramene les candidats par un `LIKE`,
     * qui se comporte pareil partout, puis on tranche en PHP sur la valeur decodee. C'est plus
     * verbeux qu'une clause SQL, et c'est le seul moyen d'etre juste sur les deux moteurs.
     *
     * @return array<int, object>
     */
    private function lignesCibles(array $cible): array
    {
        $candidats = DB::table($cible['table'])
            ->where('slug', 'like', '%'.$cible['slug'].'%')
            ->get(['id', 'slug', $cible['colonne']]);

        // Le LIKE est volontairement large : « anonymisation » y attraperait aussi
        // « desanonymisation ». C'est la comparaison EXACTE ci-dessous qui tranche, jamais le LIKE.
        return array_values(array_filter(
            $candidats->all(),
            fn ($ligne) => $this->slugCorrespond((string) $ligne->slug, $cible['slug'])
        ));
    }

    /** Vrai si le slug stocke designe bien le terme vise, qu'il soit une chaine nue ou du JSON. */
    private function slugCorrespond(string $stocke, string $attendu): bool
    {
        if ($stocke === $attendu) {
            return true;
        }

        $traductions = json_decode($stocke, true);

        return is_array($traductions) && in_array($attendu, $traductions, true);
    }

    /**
     * Remplace la clause qu'elle soit stockee avec ses accents litteraux (colonne texte simple)
     * ou avec les sequences d'echappement de json_encode (colonne JSON traduisible Spatie).
     *
     * Les deux passes sont inconditionnelles : celle qui ne correspond pas a la representation
     * reelle du champ ne trouve rien et reste sans effet. C'est volontaire - deviner laquelle
     * s'applique demanderait de deviner le type de la colonne, et c'est exactement le genre de
     * deduction qui a produit les deux echecs silencieux decrits en tete de fichier.
     */
    private function remplacerDansLesDeuxRepresentations(string $valeur, string $de, string $vers): string
    {
        $valeur = str_replace($de, $vers, $valeur);

        return str_replace($this->versEchappementJson($de), $this->versEchappementJson($vers), $valeur);
    }

    /** Rend la forme exacte qu'une chaine prend a l'interieur d'un champ JSON, guillemets retires. */
    private function versEchappementJson(string $texte): string
    {
        return trim((string) json_encode($texte), '"');
    }
};
