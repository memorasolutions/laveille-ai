<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajoute l'alias « Codex » (majuscule stricte) à la fiche « OpenAI Codex » (slug `codex`, 2026-08-28).
 *
 * MANDAT : la fiche ne se liait à RIEN sur le site. Prémisse vérifiée avant d'écrire quoi que ce
 * soit (pas supposée) : `aliases` = [] et `match_strategy` = `case_sensitive` étaient DÉJÀ posés
 * par la migration d'origine (2026_08_27_110000_add_codex_term.php). Le linkifier ne pose donc un
 * lien QUE sur la chaîne exacte « OpenAI Codex » - or le corps du site écrit presque toujours
 * « Codex » seul.
 *
 * POURQUOI LA MIGRATION D'ORIGINE AVAIT ÉCARTÉ CET ALIAS, et pourquoi ce risque a été revérifié
 * plutôt que supposé réglé par la seule casse stricte : son propre docblock démontre, en lisant
 * GlossaryLinkifier::matchInText(), qu'un alias nu « Codex » matche comme SOUS-CHAÎNE dans
 * « Codex Alimentarius » (la frontière de fin autorise un espace suivant : elle borne un MOT, pas
 * une phrase). La casse stricte protège contre le nom commun français minuscule (« un codex »,
 * manuscrit ancien - Larousse), PAS contre un AUTRE nom propre qui commencerait aussi par « Codex »
 * majuscule.
 *
 * CONTRÔLE RÉEL FAIT AVANT D'ÉCRIRE CETTE MIGRATION (mesuré, pas supposé sûr), sur la base MySQL
 * locale, `LIKE '%odex%'` casse-insensible pour ne rien manquer, sur TOUT le texte réel du site
 * (les DEUX familles d'auto-lien qui partagent ce linkifier - glossaire ET acronymes - plus
 * l'annuaire, 3e famille chargée par la même classe) :
 *  - news_articles (title/title_fr/description/summary/structured_summary) : 0 occurrence.
 *  - articles, le module blog (title/content/excerpt) : 1 occurrence textuelle réelle, et elle est
 *    en MINUSCULES - « les codex manuscrits » (article sur l'histoire des innovations technologiques,
 *    à propos de Johannes Trithemius) : exactement le sens manuscrit que la casse stricte doit
 *    exclure. Vérifié par grep exact-casse sur le contenu : AUCUNE occurrence de « Codex » majuscule
 *    dans ce corpus. (Le seul autre hit était « astralcodexten.com », une URL dans un attribut
 *    href - hors de portée du linkifier, qui ne parcourt que les noeuds texte, et de toute façon
 *    situé dans un tag <a>, déjà ignoré par walkAndReplace().)
 *  - dictionary_terms (hors la fiche codex elle-même) : 1 occurrence, fiche `greg-brockman` - « il
 *    dirige désormais la stratégie produit, incluant la fusion de ChatGPT et de Codex » : mention
 *    légitime d'OpenAI, actuellement sans lien - exactement le manque que cette migration corrige.
 *  - acronyms (acronym/full_name/description/faq/aliases/example/did_you_know) : 0 occurrence.
 *  - directory_tools, donc /annuaire/ (name/aliases/description/short_description/review/faq/pros/
 *    cons/how_to_use/core_features/use_cases) : 37 lignes. Relues UNE À UNE (majuscule ET contexte) :
 *    TOUTES désignent le Codex d'OpenAI (« Claude Code, Codex, Gemini CLI », « OpenAI Codex »,
 *    « Codex Desktop », « Codex Record & Replay d'OpenAI », « agents Codex »...) - aucune ne porte
 *    un sens concurrent. Y compris la ligne la plus inquiétante au premier regard, l'outil
 *    « Codex Pets » (id 623) : sa description « Animated companions for your Codex workflow »
 *    confirme un compagnon pour le VRAI Codex d'OpenAI, pas une marque distincte.
 *  - AUCUNE occurrence de « Codex Alimentarius », « CODEX » (groupe de piratage vidéoludique) ni
 *    d'un autre sens concurrent capitalisé n'a été trouvée dans le contenu réel actuel. Le risque
 *    documenté par la migration d'origine reste RÉEL EN THÉORIE (la frontière de mot ne protège
 *    qu'un seul mot, jamais une phrase entière), mais n'est constaté nulle part dans le corpus
 *    actuel après recherche exhaustive - pas un sondage.
 *
 * DÉCISION, ce risque résiduel assumé et écrit plutôt que caché : poser l'alias. Le gain déjà
 * mesuré (des dizaines de mentions légitimes publiées, jamais liées) l'emporte sur un risque qui
 * reste hypothétique dans tout le corpus vérifié. Si un « Codex Alimentarius » ou équivalent entre
 * un jour dans une actualité IA (peu probable sur ce site), le contrôle des auto-liens qui suit
 * chaque publication doit le rattraper - ceci n'est pas une garantie structurelle permanente.
 *
 * IDEMPOTENTE : l'alias est fusionné sans doublon (array_unique), la migration peut se rejouer.
 * RÉVERSIBLE : down() retire UNIQUEMENT « Codex », jamais tout le tableau `aliases` (au cas où un
 * autre alias aurait été posé entretemps par une migration différente) - rétablit l'état exact
 * d'avant (aliases = []), pas un vidage aveugle du champ.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */
return new class extends Migration
{
    private const SLUG = 'codex';

    private const ALIAS = ['Codex'];

    private function terme(): ?Term
    {
        // `slug` est TRADUISIBLE (Spatie) : la colonne contient un JSON, et `where('slug', ...)`
        // compare ce JSON entier à une chaîne simple - donc ne correspond JAMAIS.
        return Term::where('slug->fr_CA', self::SLUG)->first()
            ?? Term::where('slug->fr', self::SLUG)->first();
    }

    public function up(): void
    {
        $terme = $this->terme();
        if (! $terme) {
            // Base locale désynchronisée de la production (fiche pas encore migrée ici) : on ne
            // crée SURTOUT pas la fiche depuis cette migration, ce n'est pas son rôle.
            return;
        }

        $terme->aliases = array_values(array_unique(array_merge($terme->aliases ?? [], self::ALIAS)));
        $terme->save();
    }

    public function down(): void
    {
        $terme = $this->terme();
        if (! $terme) {
            return;
        }

        $terme->aliases = array_values(array_diff($terme->aliases ?? [], self::ALIAS));
        $terme->save();
    }
};
