<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "Mistral" au glossaire (2026-08-30) - l'ÉDITEUR et sa famille de modèles,
 * jamais le produit de clavardage.
 *
 * ORIGINE DU MANDAT : défaut mesuré le 2026-08-30 sur 24 pages ciblées (8 actualités au slug
 * "mistral", les 2 pages annuaire du produit, 12 fiches glossaire/hub adjacentes) - 13 pages sur
 * 24 posaient un lien "Mistral" vers /glossaire/mistral-le-chat (53 insertions), quasi toutes au
 * sens ÉDITEUR ou FAMILLE DE MODÈLES ("Mistral Small/Medium/Large", "Magistral", "la famille
 * Mistral", "fondateur de Mistral"), jamais spécifiquement le produit Le Chat. Vérifié par 404 sur
 * /glossaire/mistral-ai et /glossaire/mistral : aucune fiche générique n'existait pour l'éditeur,
 * le lieur n'avait donc qu'une seule cible possible et elle était trop étroite.
 *
 * CONTRÔLE ANTI-DOUBLON vérifié AVANT rédaction, sur les slugs réels du sitemap de production
 * (jamais un sondage d'URL devinée) : grep -i "mistral" sur l'intégralité des <loc> du sitemap
 * (4323 URL) ne renvoie qu'UNE fiche glossaire, "mistral-le-chat" (nom affiché "Mistral (Le
 * Chat)"), plus les pages annuaire du même produit et des actualités. Confirmé aussi en base
 * locale (dictionary_terms, collation utf8mb4_bin) : aucune ligne dont le name ou les aliases
 * contiennent "istral". Verdict retenu : notion VOISINE mais DISTINCTE (cas d'école "Anthropic /
 * claude-anthropic" du 2026-08-27, lui-même cas d'école "PostgreSQL / licence PostgreSQL" du skill
 * /glossaire section 0bis) - fiche nouvelle sur l'ENTREPRISE et sa famille de modèles ouverts, le
 * produit Le Chat restant intégralement sur mistral-le-chat.
 *
 * CAS JUMEAU CONSULTÉ (mémoire projet linkifier-qualifier-fabricant-2026-08-23, "Gemini (Google)"
 * → correction QUALIFIER_ORGANISATION) : ce n'est PAS le même mécanisme. Le nom de la fiche
 * existante est "Mistral (Le Chat)" - la BASE du couple "X (Y)" est ici le nom du FABRICANT, alors
 * que dans le cas Gemini/Google c'était le QUALIFIER entre parenthèses qui l'était. Lu dans le code
 * réel de GlossaryLinkifier::extractQualifierAliases() (Modules/Core/app/Services/
 * GlossaryLinkifier.php) : la base "$m[1]" d'un nom "X (Y)" est ajoutée à $out de façon
 * INCONDITIONNELLE, avant même la vérification QUALIFIER_ORGANISATION - celle-ci ne protège QUE le
 * qualifier (la partie entre parenthèses), jamais la base. "Mistral (Le Chat)" dérive donc "Mistral"
 * comme alias, sans passer par la protection posée le 2026-08-23. C'est une variante inédite du même
 * défaut de fond ("un mot qui nomme le fabricant ne doit jamais être promu synonyme d'un seul de ses
 * produits"), pas un doublon exact du correctif existant.
 *
 * REMÈDE, déjà prévu par le mécanisme ALIAS_NEVER_AUTO (même fichier, ajouté le 2026-08-29 pour
 * "cnn"/"dos"/"requête"/"requêtes"/"témoin") : "mistral" y est ajouté dans ce même lot de travail
 * (voir diff GlossaryLinkifier.php, bump de cache v14→v15). Cette liste bloque un alias DÉRIVÉ quelle
 * que soit son origine (qualifier, curé, morphologique) mais ne touche JAMAIS le nom PRINCIPAL d'une
 * fiche - donc "Mistral (Le Chat)" reste trouvable sous son titre complet, et la présente fiche,
 * dont le nom PRINCIPAL est "Mistral" seul, capte légitimement le mot bare sans concurrence. Une
 * seconde raison, propre à cette fiche, justifiait la même liste : extractMorphologicalAliases()
 * dérive automatiquement une forme minuscule ("mistral") du nom principal - or "mistral" est aussi
 * un nom commun français (le vent du sud de la France, cf. `did_you_know` de la fiche), et cette
 * forme dérivée minuscule aurait hérité de la même match_strategy que le terme (case_sensitive),
 * donc aurait pu lier tout "mistral" minuscule du site, y compris hors contexte IA. Bloquer
 * "mistral" dans ALIAS_NEVER_AUTO neutralise les DEUX chemins (qualifier de l'ancienne fiche ET
 * dérivation morphologique de la nouvelle) par un seul ajout.
 *
 * MATCH_STRATEGY = case_sensitive choisi pour la même raison que la fiche "Anthropic" du
 * 2026-08-27 (collision avec l'adjectif "anthropique") : le nom "Mistral" entre en collision avec
 * le nom commun français "mistral" (vent du sud de la France - c'est cette étymologie que
 * l'entreprise revendique, sourcée ci-dessous). La casse stricte élimine l'usage courant du mot
 * (toujours minuscule en français : "le mistral souffle"), ne laissant qu'un résidu documenté et
 * assumé (une phrase qui commencerait par "Mistral" pour parler du vent) - improbable sur un site
 * exclusivement consacré à l'actualité technologique. Alias "Mistral AI" ajouté en complément :
 * corrige au passage une fragmentation visible sur les pages testées ("Mistral" lié seul, puis
 * "AI" relié séparément à l'acronyme /acronymes-education/ai) en captant la locution complète en
 * un seul lien vers la présente fiche.
 *
 * RECHERCHE - mcp__perplexity-pro-playwright__pp_search (reprise après ia-sync, session
 * initialement déconnectée), validation croisée par au moins deux sources indépendantes par fait,
 * chaque URL retenue vérifiée HTTP 200 individuellement (curl avec user-agent navigateur) :
 *  - Fondation (28 avril 2023, Paris, SAS, SIREN 952 418 325, siège 15 rue des Halles 75001 Paris)
 *    et fondateurs (Arthur Mensch, ex-Google DeepMind, CEO; Guillaume Lample, ex-Meta AI/FAIR,
 *    Chief Science Officer; Timothée Lacroix, ex-Meta AI/FAIR, CTO) : Wikipédia FR "Mistral AI" +
 *    page officielle mistral.ai/about + fiche registre officiel français annuaire-entreprises.
 *    data.gouv.fr (SIREN 952418325), trois sources concordantes. La fiche registre et la page
 *    /about n'étant pas des articles datés (pas d'année/auteur unique attribuable), elles ne sont
 *    PAS reprises dans le champ `sources` (qui exige année ET auteur, standard durci le 2026-07-05)
 *    - seulement en `reference_url`, à l'instar du choix fait pour anthropic.com/company sur la
 *    fiche Anthropic.
 *  - Premier modèle ouvert (Mistral 7B, licence Apache 2.0, "utilisable sans restriction, y compris
 *    localement") : annonce officielle mistral.ai/news/announcing-mistral-7b/, datePublished
 *    JSON-LD confirmé "2023-09-27T08:00:00.000Z" par lecture directe du HTML rendu.
 *  - Levée de série C et actionnariat (1,7 milliard d'euros, valorisation portée à 11,7 milliards
 *    d'euros, ASML devenu premier actionnaire), septembre 2025 : communiqué officiel ASML
 *    ("ASML and Mistral AI enter strategic partnership") + EU-Startups (article daté, autrice
 *    identifiable via la publication), concordants sur le montant et l'identité de l'investisseur
 *    principal. Reuters, qui couvre le même fait, a été lu via l'extrait Perplexity mais son URL
 *    renvoie 401 en accès direct (mur d'authentification) - non retenue comme URL de `sources` par
 *    prudence, conservée uniquement comme corroboration de lecture (même traitement que Britannica
 *    sur la fiche Anthropic).
 *
 * Angle retenu : ENTREPRISE ET FAMILLE DE MODÈLES (fondation, positionnement souveraineté
 * européenne, gamme Small/Medium/Large/Magistral, licences) - le produit Le Chat, son interface et
 * sa tarification restent intégralement sur mistral-le-chat, conformément au mandat.
 *
 * Typographie OQLF (espace insécable U+00A0 réelle autour de « Mistral » dans `did_you_know`,
 * aucune espace avant ; ! ?) - contrôle `grep -nP '(?<! ) :|\s+[;!?]'` passé sur le fichier de
 * données (aucune correspondance). Aucun tiret cadratin (recherche du caractère U+2014 dans le
 * fichier de données : aucune correspondance).
 *
 * Image : public/images/glossaire/mistral.{webp,jpg} déposée AVANT cette migration (1200x669,
 * compressée magick+cwebp), générée via /nanobanana (Playwright, compte stephane@memora.ca,
 * gemini.google.com) - vent stylisé/abstrait teal/orange balayant un noyau de réseau de neurones,
 * aucun logo, aucune marque, aucune identité visuelle réelle de Mistral. Volontairement distincte
 * de l'image du produit sur mistral-le-chat.webp.
 *
 * Données dans database/data/glossaire-batch-2026-08-30-mistral.json.
 * Anti-doublon : skip si le slug existe déjà. RÉVERSIBLE : down() supprime ce terme ET retire
 * "mistral" des broader_slugs de mistral-le-chat sans toucher au reste de ses données.
 */
return new class extends Migration
{
    private const RELATED_SLUGS = ['mistral-le-chat'];

    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-30-mistral.json';
        if (! is_file($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?: [];
    }

    private function resolveCategoryId(string $slug): ?int
    {
        return Category::where('slug->fr_CA', $slug)->value('id')
            ?? Category::where('slug->fr', $slug)->value('id');
    }

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }
        if (! class_exists(Term::class) || ! class_exists(Category::class)) {
            echo "[glossaire] modèle Term/Category absent - ignoré\n";

            return;
        }

        $fallbackCatId = $this->resolveCategoryId('intelligence-artificielle');
        $allTerms = $this->terms();

        foreach ($allTerms as $i => $t) {
            if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
                echo "[glossaire] slug déjà présent, skip : {$t['slug']}\n";

                continue;
            }

            $catId = $this->resolveCategoryId($t['cat_slug']) ?? $fallbackCatId;

            $term = new Term();
            foreach (['name', 'slug', 'definition', 'analogy', 'example', 'did_you_know', 'one_sentence_answer'] as $tf) {
                $term->setTranslations($tf, ['fr_CA' => $t[$tf], 'fr' => $t[$tf]]);
            }
            $term->faq = $t['faq'];
            $term->sources = $t['sources'];
            $term->aliases = $t['aliases'] ?? [];
            $term->broader_slugs = $t['broader_slugs'] ?? [];
            $term->narrower_slugs = $t['narrower_slugs'] ?? [];
            $term->difficulty = $t['difficulty'];
            $term->icon = $t['icon'];
            $term->type = $t['type'];
            $term->acronym_full = $t['acronym_full'] ?? null;
            $term->dictionary_category_id = $catId;
            $term->hero_image = ! empty($t['has_image']) ? 'images/glossaire/'.$t['slug'].'.webp' : null;
            $term->reference_url = $t['reference_url'] ?? null;
            $term->reference_label = $t['reference_label'] ?? null;
            $term->is_published = true;
            $term->match_strategy = $t['match_strategy'] ?? 'loose';
            $term->sort_order = 900 + $i;
            $term->save();

            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }

        // Rattache le produit Le Chat à son éditeur : ajoute "mistral" à broader_slugs SANS
        // écraser le reste du tableau (append dynamique sur l'état lu au moment de la migration).
        foreach (self::RELATED_SLUGS as $slug) {
            $related = Term::where('slug->fr_CA', $slug)->first();
            if (! $related) {
                echo "[glossaire] terme lié absent, skip broader_slugs : {$slug}\n";

                continue;
            }
            $existing = is_array($related->broader_slugs) ? $related->broader_slugs : [];
            if (in_array('mistral', $existing, true)) {
                continue;
            }
            $related->broader_slugs = array_values([...$existing, 'mistral']);
            $related->save();
            echo "[glossaire] broader_slugs mis à jour : {$slug} (+mistral)\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach (self::RELATED_SLUGS as $slug) {
            $related = Term::where('slug->fr_CA', $slug)->first();
            if (! $related) {
                continue;
            }
            $existing = is_array($related->broader_slugs) ? $related->broader_slugs : [];
            $related->broader_slugs = array_values(array_diff($existing, ['mistral']));
            $related->save();
        }

        foreach ($this->terms() as $t) {
            Term::where('slug->fr_CA', $t['slug'])->delete();
        }
    }
};
