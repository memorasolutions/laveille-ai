<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "BDH-CQ" au glossaire (2026-09-01) - le modèle de raisonnement de Pathway
 * (préprint arXiv:2608.09888, 10 août 2026), déclencheur de la fiche d'actualité laveille.ai
 * "Sans réfléchir « à voix haute », ce modèle inspiré du cerveau coûte jusqu'à 11 fois moins
 * cher qu'OpenAI" (slug sans-reflechir-a-voix-haute-ce-modele-inspire-du-cerveau-coute-jusqua-
 * 11-fois-moins-cher-quopenai).
 *
 * ⚠️ PAS D'IMAGE dans ce cycle (décision explicite, pas un oubli) : quatre agents auraient eu
 * besoin du navigateur Gemini en même temps pour produire simultanément les fiches BDH-CQ,
 * Pathway, arXiv et une quatrième, alors qu'un seul contexte de navigation existe par lot
 * (mémoire projet session-partagee-un-contexte-par-lot-2026-09-01, écrite ce matin même après
 * l'invalidation d'une session par des contextes concurrents). has_image=false ici ; le prompt
 * d'image prêt à l'emploi est remis au rapport de la session pour une passe groupée ultérieure,
 * suivi par un todo explicite plutôt que silencieusement laissé de côté.
 *
 * CONTRÔLE ANTI-DOUBLON fait AVANT rédaction, sur DEUX bases indépendantes, pour TROIS noms
 * (BDH-CQ, Dragon Hatchling, Pathway - la consigne demandait aussi de vérifier "Pathway" même si
 * sa fiche propre est traitée par un agent frère) :
 *  1. Sitemap de production réel (jamais une URL devinée) : `curl sitemap.xml` → 520 fiches
 *     glossaire, grep -iE "bdh|dragon|hatchling|pathway|arxiv|arc-agi|arc-prize" → AUCUNE
 *     correspondance. Même contrôle étendu à /acronymes-education/ (311 entrées) et /annuaire/
 *     (2196 entrées) pour "bdh" spécifiquement (alias envisagé) : aucune correspondance non plus.
 *  2. Base locale (151 termes, partielle - ne prouve jamais une absence à elle seule, sert de
 *     second regard) : requête sur `name` ET `aliases` (les deux, jamais name seul) pour les
 *     mêmes mots-clés via Term::query()->get(...) - aucune correspondance.
 * Verdict : aucun doublon, fiche nouvelle légitime. "Dragon Hatchling" et "Pathway" restent
 * également absents - la présente migration ne les crée PAS (hors mandat : "Pathway" et "arXiv"
 * sont traités par des agents frères dans ce même lot, voir plus bas ; "Dragon Hatchling" en
 * tant qu'architecture-famille distincte de BDH-CQ n'était pas au mandat et reste un bon candidat
 * de fiche future, noté au rapport).
 *
 * ACRONYME - recherche menée EXACTEMENT comme demandé ("ne déduis rien des lettres, va lire ce
 * que la préimpression écrit elle-même"), sur le texte INTÉGRAL des DEUX préprints (HTML complet
 * récupéré et lu, pas seulement l'abstract), plus le dépôt GitHub officiel :
 *  - arXiv:2608.09888 (BDH-CQ) : citation exacte trouvée dans le corps du texte -
 *    "We use 'BDH' for the architectural family and 'BDH-CQ' for the system introduced here."
 *    Aucune occurrence, nulle part dans les ~180 Ko de texte intégral, d'un développement
 *    lettre par lettre de "CQ" (recherche des motifs "stands for", "short for", "abbreviat",
 *    "acronym" : zéro résultat). Perplexity, interrogé sur le sens de "CQ", n'a proposé que des
 *    hypothèses non sourcées ("très vraisemblablement Continuous Query", puis "très probablement
 *    Context-Query" dans une seconde requête - DEUX réponses différentes et contradictoires à la
 *    même question, aucune ancrée dans une citation réelle) - exactement le piège de déduction
 *    que la consigne demandait d'éviter.
 *  - arXiv:2509.26507 (Dragon Hatchling, 30 septembre 2025, papier fondateur de "BDH") : citation
 *    exacte de l'abstract - "We introduce `Dragon Hatchling' (BDH), a new Large Language Model
 *    architecture...". "Dragon Hatchling" ne compte que deux mots (D, H) : le "B" de "BDH" n'est
 *    JAMAIS expliqué dans le texte intégral (recherche du mot "Baby" sur ~1,2 Mo de HTML complet :
 *    zéro occurrence).
 *  - github.com/pathwaycom/bdh (dépôt officiel Pathway, vérifié directement, HTTP 200) : titre et
 *    description "BDH (Dragon Hatchling) - Architecture and Code", jamais "Baby Dragon Hatchling"
 *    nulle part sur la page (zéro occurrence de "Baby"). Ceci CONTREDIT directement une réponse de
 *    Perplexity qui affirmait que le dépôt GitHub développait "Baby Dragon Hatchling" - vérifié
 *    faux par lecture directe de la page.
 * DÉCISION : acronym_full = null. Développer un acronyme non développé par ses propres auteurs
 * aurait été la faute exacte que la consigne du skill dénonce ("un acronyme mal développé dans un
 * glossaire est une faute qui se propage"). La filiation "BDH-CQ" ⊂ famille "BDH" ⊂ nom complet
 * "Dragon Hatchling" est expliquée en toutes lettres dans `definition`, sourcée aux deux préprints.
 *
 * ALIAS "BDH" seul - ENVISAGÉ puis ÉCARTÉ après vérification réelle (pas seulement par prudence
 * théorique) : recherche Perplexity sur le sens courant de "BDH" hors IA → collision RÉELLE et
 * documentée, "Banque de données hydriques (BDH)", sigle employé par le ministère québécois de
 * l'Environnement (quebec.ca) dans ses documents publics. match_strategy=case_sensitive
 * n'aurait rien réglé ici (les deux sens s'écrivent identiquement en majuscules "BDH", aucune
 * distinction de casse ne les sépare, contrairement au cas "Mistral"/"mistral"). Le site porte une
 * section "Acronymes éducation" (312 sigles québécois) confirmant que ce registre gouvernemental
 * n'est pas hors-sujet pour laveille.ai. Alias écarté, aucun alias retenu ; seul le nom complet
 * "BDH-CQ" (sans collision connue, jamais employé pour autre chose) capte les mentions.
 *
 * RELATIONS - broader_slugs=["reasoning-models","reseau-de-neurones"], les deux slugs vérifiés
 * EN LIGNE avant rédaction (curl https://laveille.ai/glossaire/{slug}, HTTP 200 les deux) :
 *  - "reasoning-models" (Modèles de raisonnement) : justifié par la première phrase du préprint
 *    lui-même - "We introduce BDH-CQ, a reasoning model...". Cette fiche n'avait AUCUN terme lié
 *    (aucune section "Termes liés" sur la page rendue) : BDH-CQ y est donc ajouté en retour dans
 *    narrower_slugs (voir up(), append non destructif, symétrique au mécanisme déjà utilisé pour
 *    "mistral"/"mistral-le-chat" le 2026-08-30, adapté au sens narrower plutôt que broader).
 *  - "reseau-de-neurones" (Réseau de neurones) : justifié par le préprint fondateur BDH -
 *    "a new...architecture based on a scale-free biologically inspired network of n
 *    locally-interacting neuron particles". Cette fiche a DÉJÀ une liste de sous-termes curatée
 *    (CNN, RNN, Transformer, Perceptron, Auto-encodeur, Auto-encodeur parcimonieux) - toutes des
 *    FAMILLES D'ARCHITECTURE générales. BDH-CQ est un SYSTÈME précis (une configuration à 150M
 *    paramètres), pas une famille d'architecture au même niveau que ses voisins de cette liste -
 *    la famille elle-même ("Dragon Hatchling"/"BDH") n'a pas encore sa propre fiche. Décision :
 *    broader_slugs le référence (relation vraie et vérifiable), mais narrower_slugs de
 *    "reseau-de-neurones" n'est PAS modifié, pour ne pas mélanger un niveau de granularité
 *    "système précis" dans une liste par ailleurs réservée aux familles - laissé au rapport comme
 *    nuance assumée plutôt que comme un oubli.
 * "pathway" et "arxiv" DÉLIBÉRÉMENT ABSENTS de broader_slugs/narrower_slugs : les deux fiches
 * sœurs existent déjà comme migrations locales complètes au moment d'écrire ceci
 * (2026_09_01_100000_add_pathway_term.php slug="pathway", 2026_09_01_140000_add_arxiv_term.php
 * slug="arxiv") mais ne sont PAS encore en ligne (non déployées) - conformément à la consigne
 * ("si une fiche sœur n'est pas encore publiée, ne fabrique pas son slug au hasard, vérifie, et
 * si absent dis-le au rapport plutôt que de pointer dans le vide"), aucune relation n'est câblée
 * en dur vers elles ici. Au-delà de la prudence de calendrier, la relation "éditeur"/"dépôt" n'est
 * de toute façon PAS une hiérarchie broader/narrower au sens de ce module (précédent déjà posé le
 * 2026-08-27 sur la fiche Palisade Research : un ORGANISME n'est jamais rattaché en broader/
 * narrower à ce qu'il produit ; précédent AlphaFold, dont broader_slugs=["transformer"], jamais
 * l'organisme qui l'a créé). Les mots "Pathway" et "arXiv" apparaissent tels quels dans
 * `definition`/`example` ci-dessous : l'auto-lien du site créera le lien croisé automatiquement
 * dès que ces deux fiches seront déployées, sans intervention supplémentaire.
 *
 * SOURCES - chaque URL vérifiée par requête réelle (curl direct, HTTP 200), texte intégral lu,
 * jamais une adresse devinée :
 *  - https://arxiv.org/abs/2608.09888 (source PRIMAIRE) : abstract et métadonnées confirmés en
 *    direct - 9 auteurs (Björn Engdahl, Adrian Kosowski, Jan Chorowski, Zuzanna Stamirowska,
 *    Przemysław Uznański, Junlin Jiang, Rohan Phadke, Remigiusz Kinas, Richard Zhong),
 *    affiliations Pathway/Bielik AI/New York University, catégorie cs.NE, déposé 10 août 2026.
 *    Chiffres repris du corps du texte intégral (pas seulement l'abstract) : 29,5 % pass@2 à
 *    0,0007 $ US/tâche ; comparaison "ARC Prize's reported costs as of July 2026" à GPT-5.6 Luna
 *    (Low), 34,2 % à 0,040 $ US/tâche, soit 57× moins cher aux prix de juillet 2026 ou 11× après
 *    la baisse de prix de 80 % appliquée par OpenAI le 30 juillet 2026. Ces mêmes chiffres sont
 *    corroborés par la fiche d'actualité laveille.ai déjà publiée et vérifiée contre sa source
 *    (déclencheur de la présente fiche) - double confirmation, pas une source unique.
 *  - https://arxiv.org/abs/2509.26507 (source PRIMAIRE, papier fondateur de BDH) : abstract et
 *    auteurs confirmés en direct (Adrian Kosowski, Przemysław Uznański, Jan Chorowski, Zuzanna
 *    Stamirowska, Michał Bartoszkiewicz), déposé 30 septembre 2025.
 * `pp_search` disponible et utilisé pour les recherches d'appoint (sens de "BDH" hors IA, tentative
 * de trouver la page ARC Prize Foundation) ; les faits chiffrés retenus dans la fiche proviennent
 * tous du texte intégral des préprints lus directement, jamais d'un résumé de recherche non
 * recoupé.
 *
 * Typographie OQLF vérifiée par script (espace insécable U+00A0 réelle avant ':', AUCUNE espace,
 * même insécable, avant ';' '!' '?' - piège du réflexe français explicitement testé et corrigé
 * trois fois dans les questions de la FAQ avant validation finale). Aucun tiret cadratin (U+2014)
 * dans aucun champ (contrôle programmatique). Comptage de mots vérifié par script pour chaque
 * champ (definition 157, analogy 23, example 39, did_you_know 32, one_sentence_answer 40/40 max).
 *
 * Données dans database/data/glossaire-batch-2026-09-01-bdh-cq.json.
 * Anti-doublon à l'exécution : skip si le slug existe déjà, migration rejouable.
 * RÉVERSIBLE : down() supprime le terme ET retire "bdh-cq" du narrower_slugs de "reasoning-models"
 * sans toucher au reste de ses données.
 */
return new class extends Migration
{
    private const SLUGS = ['bdh-cq'];

    private const RELATED_NARROWER = ['reasoning-models'];

    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-09-01-bdh-cq.json';
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
            echo "[glossaire] modèle Term/Category absent, ignoré\n";

            return;
        }

        $fallbackCatId = $this->resolveCategoryId('intelligence-artificielle');

        foreach ($this->terms() as $i => $t) {
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
            // PAS D'IMAGE dans ce cycle (has_image=false dans les données) : hero_image reste
            // null, la fiche part en production sans image, décision explicite documentée
            // ci-dessus, suivie par un todo pour la passe groupée ultérieure.
            $term->hero_image = ! empty($t['has_image']) ? 'images/glossaire/'.$t['slug'].'.webp' : null;
            $term->reference_url = $t['reference_url'] ?? null;
            $term->reference_label = $t['reference_label'] ?? null;
            $term->is_published = true;
            $term->match_strategy = $t['match_strategy'] ?? 'loose';
            $term->sort_order = 950 + $i;
            $term->save();

            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }

        // Rattache "bdh-cq" en retour dans le narrower_slugs de "reasoning-models" (sa fiche
        // n'avait aucun terme lié jusqu'ici) - append non destructif sur l'état lu à l'exécution.
        foreach (self::RELATED_NARROWER as $slug) {
            $related = Term::where('slug->fr_CA', $slug)->first();
            if (! $related) {
                echo "[glossaire] terme lié absent, skip narrower_slugs : {$slug}\n";

                continue;
            }
            $existing = is_array($related->narrower_slugs) ? $related->narrower_slugs : [];
            if (in_array('bdh-cq', $existing, true)) {
                continue;
            }
            $related->narrower_slugs = array_values([...$existing, 'bdh-cq']);
            $related->save();
            echo "[glossaire] narrower_slugs mis à jour : {$slug} (+bdh-cq)\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach (self::RELATED_NARROWER as $slug) {
            $related = Term::where('slug->fr_CA', $slug)->first();
            if (! $related) {
                continue;
            }
            $existing = is_array($related->narrower_slugs) ? $related->narrower_slugs : [];
            $related->narrower_slugs = array_values(array_diff($existing, ['bdh-cq']));
            $related->save();
        }

        foreach (self::SLUGS as $slug) {
            Term::where('slug->fr_CA', $slug)->delete();
        }
    }
};
