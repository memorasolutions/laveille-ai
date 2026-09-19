<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Term;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ⚠ CORRECTION APPORTEE LE MEME JOUR (2026-09-18), A LIRE AVANT LE RESTE DE CE DOCBLOCK :
 * L EXPLICATION CAUSALE CI-DESSOUS EST FAUSSE, DEMONTREE PAR LE COMMIT SUIVANT (695f319be). Le
 * linkifier auto-lie le NOM du terme de glossaire, jamais ses alias - et le nom de la fiche est
 * « Hermes ». Retirer un alias homonyme du nom ne pouvait donc RIEN changer au defaut mesure sur
 * /annuaire/hermes-desktop : le lien partait du champ `name`, jamais de la liste `aliases`. Le
 * CHANGELOG a ete corrige des cette decouverte ; ce docblock, lui, ne l avait pas encore ete.
 *
 * CE QUE CETTE MIGRATION FAIT REELLEMENT : un nettoyage NEUTRE, sans effet sur le linkifier ni
 * sur aucun lien affiche. Elle retire un alias devenu redondant avec le nom du terme (« Hermes »
 * figurait a la fois comme `name` et comme entree de `aliases`) ; l alias « Hermes Agent », lui,
 * reste utile et est conserve.
 *
 * LE VRAI CORRECTIF du defaut mesure (les cinq liens « Hermes » coupant « Desktop » en texte nu)
 * est GlossaryLinkifier::TOOL_SUFFIX_COMPOUND_EXCLUSIONS['hermes'] => ['desktop'], livre en
 * v1.289.11 (Modules/Core/app/Services/GlossaryLinkifier.php) : sur sa PROPRE page, une fiche
 * d annuaire ne s auto-lie pas, donc la forme longue « Hermes Desktop » cesse d etre candidate a
 * cet endroit precis et le nom court gagnait par defaut - c est ce mecanisme-la, et non le
 * retrait d alias documente plus bas, qui ecarte le faux lien.
 *
 * Retrait de l alias « Hermes » NU sur la fiche de glossaire hermes, livree quelques minutes plus
 * tot en v1.289.8. L alias « Hermes Agent » est conserve.
 *
 * LE DEFAUT, MESURE EN PRODUCTION juste apres le deploiement, et non suppose : sur la page
 * /annuaire/hermes-desktop, CINQ occurrences de « Hermes » etaient soulignees vers
 * /glossaire/hermes en laissant « Desktop » en texte nu juste apres. Le lecteur voyait
 * « Hermes Desktop » dont seule la premiere moitie est cliquable, et atterrissait sur une fiche
 * generale au lieu de rester sur la fiche du produit. Meme defaut, une fois, sur l actualite
 * consacree a Hermes Desktop.
 *
 * C EST EXACTEMENT LE DEFAUT DE FRONTIERE DECRIT DANS LE SKILL SOUS LE NOM « GPT-4o mini », MAIS
 * PAR UN CHEMIN QUE LE SKILL N AVAIT PAS PREVU, et c est la lecon a retenir. Le skill affirme que
 * le tri par longueur decroissante du linkifier protege les formes composees des lors qu elles
 * sont declarees : « Hermes Desktop » EST bien declare, sur la fiche d annuaire, et le lien
 * fonctionne correctement partout ailleurs - la preuve est dans la meme mesure. Mais sur sa PROPRE
 * page, une fiche ne s auto-lie pas. La forme longue cesse donc d etre un candidat actif a cet
 * endroit precis, le tri par longueur n a plus rien a departager, et la forme COURTE gagne par
 * defaut. La protection par longueur ne vaut que la ou la forme longue est candidate.
 *
 * TROIS OPTIONS ONT ETE PESEES, et la mesure a tranche :
 *   (a) declarer aussi « Hermes Desktop » en alias du GLOSSAIRE : ecarte, ce serait PIRE - la
 *       forme longue gagnerait alors partout et detournerait vers le glossaire les liens qui vont
 *       aujourd hui correctement vers la fiche produit de l annuaire.
 *   (b) poser une garde generique dans le linkifier : ecarte, le skill le deconseille
 *       explicitement (il faudrait trancher famille par famille, « Claude Pro » etant un niveau
 *       d offre alors que « GPT-4o mini » est un modele distinct), et c est un chantier en soi.
 *   (c) retirer l alias nu : RETENU.
 *
 * CE QUE LE RETRAIT COUTE, compte page par page sur les six pages du corpus qui citent Hermes :
 * AUCUN lien utile n est perdu. Sur /annuaire/humalike-x-hermes, « Hermes Agent » reste lie. Sur
 * les deux actualites consacrees a Hermes Agent, « Hermes Agent » reste lie. Sur
 * /annuaire/hermes-desktop et sur l actualite Hermes Desktop, les seules occurrences de « Hermes »
 * nu etaient precisement les six liens trompeurs. Le retrait supprime donc six liens faux et zero
 * lien juste - un lien faux coute plus cher qu un lien absent, parce qu il trompe au lieu de
 * manquer.
 *
 * Migration idempotente, et reversible : down() remet l alias tel qu il etait.
 */
return new class extends Migration
{
    private const SLUG = 'hermes';

    private const ALIAS_RETIRE = 'Hermes';

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! class_exists(Term::class)) {
            return;
        }

        $terme = Term::where('slug->fr_CA', self::SLUG)->first();

        if (! $terme) {
            echo "[glossaire] terme hermes absent, retrait d alias ignore\n";

            return;
        }

        $alias = $terme->aliases ?? [];

        if (! in_array(self::ALIAS_RETIRE, $alias, true)) {
            echo "[glossaire] alias deja absent, rien a faire\n";

            return;
        }

        $terme->aliases = array_values(array_diff($alias, [self::ALIAS_RETIRE]));
        $terme->save();

        echo '[glossaire] hermes : alias nu retire, restent '.json_encode($terme->aliases)."\n";
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $terme = Term::where('slug->fr_CA', self::SLUG)->first();

        if (! $terme) {
            return;
        }

        $alias = $terme->aliases ?? [];

        if (! in_array(self::ALIAS_RETIRE, $alias, true)) {
            $alias[] = self::ALIAS_RETIRE;
            $terme->aliases = $alias;
            $terme->save();
        }
    }
};
