<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "arXiv" au glossaire (2026-09-01) - le dépôt ouvert de préimpressions
 * scientifiques que la quasi-totalité de nos fiches d'actualité technique citent comme source
 * primaire (format arXiv:YYMM.NNNNN).
 *
 * CONTRÔLE ANTI-DOUBLON fait AVANT rédaction, sur DEUX sources indépendantes de production
 * (jamais un sondage d'URL devinée, cf. piège du 2026-08-21) :
 *  1. Les 520 slugs RÉELS du sitemap de production (curl https://laveille.ai/sitemap.xml) : aucun
 *     ne contient "arxiv", "preprint", "preimpression"/"pré-impression", "prepublication"/
 *     "pré-publication" ni "peer-review"/"revue-par-les-pairs".
 *  2. Requête live sur le moteur de recherche du site (/recherche/palette, la même méthode que la
 *     migration soeur WorkOS du 2026-08-27) : q=arxiv (12 résultats, sections "Actualités"/"Blog"
 *     seulement) et q=arXiv (identique, casse ignorée par le moteur) -> AUCUNE section "glossaire"
 *     dans les deux cas. Idem pour q=préimpression (3 résultats, section "Actualités" seulement) et
 *     q=prépublication (5 résultats, sections "Actualités"/"Annuaire") -> jamais de section
 *     "glossaire" non plus.
 * Décision : aucun doublon, notion absente, fiche nouvelle.
 *
 * DISTINCTION DÉLIBÉRÉE avec la notion de "préimpression"/"preprint" (mandat explicite de la
 * session) : arXiv est un DÉPÔT (un lieu, une plateforme) ; une préimpression est un TYPE DE
 * DOCUMENT (un état de publication). Ce sont deux notions distinctes qui NE DOIVENT PAS être
 * fusionnées - un lecteur qui cherche l'une ne serait pas satisfait par l'autre. Le mot
 * "preimpression" existe déjà dans ce codebase, mais UNIQUEMENT comme valeur de classification
 * interne du champ `NewsArticle::$SOURCE_TYPES` (Modules/News/app/Models/NewsArticle.php:1079,
 * "annonce_commerciale, etude_evaluee, preimpression ou message_personnel" - un enum technique du
 * pipeline actu2, jamais une fiche de glossaire). Comme "préimpression" lui-même n'a AUCUNE fiche
 * de glossaire (confirmé par les deux contrôles ci-dessus), il n'y a rien à relier via
 * broader_slugs/narrower_slugs pour cette notion précise ; si une fiche "préimpression" est créée
 * plus tard, la relation logique serait narrower_slugs=["arxiv"] côté "préimpression" (arXiv héberge
 * DES préimpressions, il n'EST pas "une sorte de" préimpression) - à faire à ce moment-là, pas ici.
 *
 * RELATIONS : broader_slugs=[] et narrower_slugs=[], choix délibéré et non un oubli. Candidats
 * examinés dans le glossaire existant : "github" et "hugging-face" (autres dépôts/plateformes) ne
 * sont ni parents ni enfants d'arXiv - ce sont des analogues fonctionnels (des dépôts pour un autre
 * type de contenu), pas une hiérarchie ; "open-source" et "donnees-ouvertes" partagent l'esprit
 * d'ouverture mais arXiv n'est "une sorte de" ni l'un ni l'autre (accès ouvert à des documents,
 * pas du code ni des jeux de données). Aucun terme "science ouverte"/"publication scientifique"
 * n'existe dans le glossaire pour servir de parent générique. Même logique que WorkOS/Palisade
 * Research/Anthropic le 2026-08-27 : pas de lien hiérarchique forcé faute de relation réelle.
 *
 * ALIAS - décision motivée sur LES DEUX pièges nommés pour cette session :
 *  - "archive" (la prononciation anglaise d'arXiv) est EXPLICITEMENT ÉCARTÉ. Vérifié en
 *    production : /recherche/palette?q=archive renvoie 23 résultats DOMINÉS par l'Internet
 *    Archive/Wayback Machine ("Le Wayback Machine... menacé", "Médias vs IA : la guerre des
 *    archives web", etc.). Ajouter "archive" en alias aurait posé des liens faux sur tout contenu
 *    parlant d'archivage web ou légal - exactement le scénario qui a coûté 3 faux liens à la fiche
 *    "clés d'accès" le 2026-08-22. "archive" est un AUTRE mot (qui sonne pareil) et non une variante
 *    de casse ou d'orthographe d'"arXiv" : la proximité phonétique n'est pas une raison de le
 *    retenir, elle est la raison précise de l'écarter.
 *  - "arxiv"/"Arxiv" (variantes de casse) NE SONT PAS ajoutées manuellement en `aliases` : lues
 *    dans le code réel de GlossaryLinkifier::extractMorphologicalAliases() (Modules/Core/app/
 *    Services/GlossaryLinkifier.php:867-891), cette fonction dérive AUTOMATIQUEMENT "arxiv"
 *    (minuscule) et "Arxiv" (initiale seule) depuis le `name`="arXiv" au moment du linkify() - même
 *    mécanisme documenté pour "WorkOS"->"workos"/"Workos" le 2026-08-27. Le garde-fou homographe
 *    (ligne 876-882, qui empêche la dérivation minuscule d'un nom propre dont la forme minuscule
 *    est un mot français courant, ex. "Transformer"->pas "transformer") ne s'applique pas ici : la
 *    première lettre d'"arXiv" est déjà minuscule ('a'), donc ce garde-fou n'entre pas en jeu, et de
 *    toute façon "arxiv" n'est un mot commun d'aucune langue - contrairement à "codex" (manuscrit
 *    ancien) ou "mistral" (vent), "arXiv" n'a pas de second sens qui pourrait collisionner.
 *  - "arxiv.org" est le SEUL alias manuel retenu, pour une raison technique précise et vérifiée
 *    dans le code réel : le calcul de fin de mot du linkifier (GlossaryLinkifier.php:1191)
 *    `$finDeMot = '(?![\p{L}\p{N}_\-\/]|\.\w)'` exclut explicitement un point suivi d'un caractère
 *    alphanumérique après le terme matché. Un texte qui écrit "arXiv.org" en prose ne matcherait
 *    JAMAIS via le nom seul (le ".o" de ".org" casse la frontière de fin de mot), alors que
 *    "arxiv.org" est une façon courante de désigner la plateforme dans un texte narratif. Ajouté
 *    comme alias littéral distinct, sa propre frontière de DÉBUT de mot (ligne 1210,
 *    `(?<![\p{L}\p{N}._\-\/])`) empêche par ailleurs tout matching à l'intérieur d'une URL complète
 *    du type "https://arxiv.org/abs/..." (le "/" qui précède "arxiv" dans "https://" bloque le
 *    début de mot) - donc aucun risque de sur-linkification dans une URL déjà cliquable.
 * match_strategy = case_sensitive, en défense en profondeur, à l'image de toutes les fiches
 * organisation/outil du même lot (WorkOS, Anthropic, Codex, Palisade Research, Z.ai).
 *
 * ANGLE ÉDITORIAL - 3 angles notés /100, choisis pour CE lectorat (laveille.ai, veille IA
 * québécoise, lecteurs non-spécialistes qui croisent des identifiants arXiv dans nos propres
 * fiches) :
 *  - Angle historique/fondation (Ginsparg, 1991, croissance à 3M+ articles) : 55/100. Contexte
 *    valide mais passif - un lecteur qui voit "arXiv:2608.09888" dans une fiche ne cherche pas
 *    d'abord une date de fondation.
 *  - Angle technique/fonctionnement (soumission, modération, catégories, API, identifiants) :
 *    60/100. Utile pour un chercheur-auteur, mais notre lectorat (enseignants, gestionnaires,
 *    curieux tech) ne publie pas sur arXiv, il LIT des articles qui EN CITENT un.
 *  - Angle "que prouve une préimpression, que ne prouve-t-elle PAS" : 92/100, RETENU. Répond
 *    exactement à la question que se pose un lecteur tombant sur "selon une étude arXiv" dans un
 *    article - la moderation d'arXiv (bonne catégorie, format, absence de plagiat évident) N'EST
 *    PAS une évaluation par les pairs, fait central de la page officielle elle-même. Ancré sur un
 *    fait daté et vérifiable (durcissement anti-IA 2025-2026), sert directement la logique AEO
 *    (réponse citable et actionnable), et sert la mission éditoriale du site (vulgariser SANS
 *    survendre une source). Le fil historique est conservé en contexte bref dans `definition`,
 *    jamais comme sujet principal.
 *
 * RECHERCHE - mcp__perplexity-pro-playwright__pp_search, chaque fait recoupé par lecture DIRECTE
 * de la page source (curl, contenu HTML/JSON-LD lu, pas seulement le résumé Perplexity) :
 *  - Fondation (14 août 1991, Paul Ginsparg, Los Alamos National Laboratory, d'abord
 *    hep-th@xxx.lanl.gov, renommé arXiv.org en 1998, hébergé depuis par Cornell University),
 *    8 domaines couverts, et citation exacte "arXiv.org now hosts more than three million
 *    scholarly articles in eight subject areas" : lus directement dans
 *    https://info.arxiv.org/about/index.html (200 OK).
 *  - FAIT CENTRAL, citation exacte lue directement sur la même page : "Material is not
 *    peer-reviewed by arXiv - the contents of arXiv submissions are wholly the responsibility of
 *    the submitter and are presented as is without warranty."
 *  - Format d'identifiant arXiv:YYMM.NNNNN (5 chiffres depuis janvier 2015, suffixe de version vN) :
 *    lu directement sur https://info.arxiv.org/help/arxiv_identifier.html (200 OK).
 *  - Rejet d'environ 2% des soumissions pour contenu IA suspect/paper mills, citation de Steinn
 *    Sigurðsson (directeur scientifique d'arXiv) : Nature, Traci Watson (PAS "Tiffany Watson" comme
 *    l'avait d'abord rapporté le résumé Perplexity - corrigé après lecture directe du JSON-LD de
 *    https://www.nature.com/articles/d41586-025-02469-y, champ "author":[{"name":"Traci Watson"}],
 *    "datePublished":"2025-08-12T00:00:00Z", statut 303->200 via redirection cookies, page réelle).
 *  - Règle de suspension d'un an pour contenu IA non déclaré (références inventées, citations
 *    inexistantes, commentaires résiduels d'un assistant IA) : CERN Courier,
 *    https://cerncourier.com/arxivs-one-strike-rule-on-ai/, lu directement (JSON-LD
 *    "datePublished":"2026-07-23T10:20:16+00:00", auteur crédité "cern" -> "CERN Courier").
 *  - astrobites.org (source historique secondaire trouvée par la recherche initiale) a été ÉCARTÉE
 *    des `sources` : curl renvoie 403 au user-agent utilisé, non vérifiable directement (même
 *    prudence que Fenwick écarté sur la fiche WorkOS du 2026-08-27).
 *
 * FAIT VÉRIFIÉ APRÈS UNE FAUSSE PISTE INITIALE (traçabilité de la vigilance zéro-invention, règle 0
 * du skill /glossaire) : le brief de session citait deux fiches d'actualité de laveille.ai
 * référençant "arXiv:2608.09888" et "arXiv:2509.26507" comme sources primaires. Recherché d'abord
 * par TROIS voies qui ont toutes échoué à confirmer - (1) /recherche/palette?q=2608.09888 et
 * q=2509.26507 -> 0 résultat chacun (l'index de recherche ne couvre pas le champ `sources`
 * structuré, non concluant seul) ; (2) contenu HTML complet de trois articles candidats trouvés via
 * la recherche "arxiv" (mold, FIBER GPU, AWS RNG) -> AUCUN des trois ne contient ces identifiants,
 * une fausse piste (le bon article ne remontait pas dans ces résultats) ; (3) requête directe sur
 * `news_articles` via cpanel_terminal -> le module Shell d'UAPI cPanel n'est pas installé sur ce
 * compte, requête impossible. **Confirmé ensuite par une QUATRIÈME voie** : le CHANGELOG.md déjà
 * commité par la migration soeur "Pathway" (2026_09_01_100000_add_pathway_term.php, même journée)
 * nommait l'article réel ; celui-ci a été lu DIRECTEMENT ici (curl, 200 OK) -
 * laveille.ai/actualites/sans-reflechir-a-voix-haute-ce-modele-inspire-du-cerveau-coute-jusqua-11-
 * fois-moins-cher-quopenai (lastmod sitemap 2026-09-01T11:11:32-04:00) - qui cite bien les DEUX
 * identifiants, avec liens réels vers https://arxiv.org/abs/2608.09888 ("BDH-CQ: In-Context
 * Learning with Recurrent Latent Reasoning", préprint Pathway, 10 août 2026) et
 * https://arxiv.org/abs/2509.26507 ("The Dragon Hatchling: The Missing Link between the
 * Transformer and Models of the Brain"). `example` cite donc ce fait, désormais vérifié de première
 * main et non plus seulement rapporté par le brief.
 *
 * Rédaction déléguée à mcp__hermes__model_invoke (task_type=synthesis, tous les faits ci-dessus
 * fournis en prompt, aucune invention laissée au modèle) puis revue et corrigée par la session
 * (retrait de l'exemple non vérifié, correction "AAMM"->"YYMM" pour respecter la notation officielle
 * réelle, retrait d'une majuscule d'emphase "PAS" incohérente avec le ton éditorial du site).
 *
 * Typographie OQLF (espace insécable U+00A0 RÉELLE avant ":" et avant "%", jamais l'entité &nbsp;,
 * aucune espace avant ";"/"!"/"?" - piège nommé dans la mémoire du projet, une première tentative
 * avait produit une espace ASCII ordinaire malgré un caractère apparemment correct à la frappe,
 * corrigé par échappement Unicode explicite   puis revérifié OCTET PAR OCTET, pas seulement
 * visuellement) : contrôle grep -nP '(?<! ) :|\s+[;!?]' passé sur le fichier de données -> aucune
 * correspondance. Aucun tiret cadratin.
 *
 * IMAGE NON LIVRÉE AVEC CETTE MIGRATION - décision du superviseur, pas un oubli (ticket #2156,
 * 2026-09-01). Le navigateur Playwright local (voie normale hors MCP, cf. docs/CONTRAINTES-SOUS-
 * AGENTS.md section 6 quinquies) était occupé en continu par d'autres agents du même lot
 * (Pathway, BDH-CQ) pendant toute la session ; plutôt qu'ouvrir un second contexte concurrent sur
 * le même jeu de témoins Google (risque mesuré le jour même : invalidation de session côté
 * serveur), la fiche est livrée SANS image, comme ses deux soeurs. `has_image=false` dans le JSON
 * de données -> `hero_image=null` -> le gabarit (Modules/Dictionary/resources/views/public/
 * show.blade.php, `dictionary_hero_image_url()`) omet proprement tout le bloc `.gl-hero-image` et
 * la meta `og:image` retombe sur le fallback générique du site (`/images/og-image.png`) - vérifié
 * en local, zéro référence cassée, zéro exception (DebugBar : "exceptions":{"count":0}).
 * Prompt d'image préparé et conservé pour une passe groupée ultérieure avec les fiches Pathway et
 * BDH-CQ (un seul contexte de navigation pour les trois) : voir rapport de session, ticket #2156.
 * À faire ensuite : générer arxiv.{webp,jpg} (1200x669, magick+cwebp), les committer suivies par
 * git, puis `UPDATE`/migration de suivi qui pose `hero_image='images/glossaire/arxiv.webp'`.
 *
 * CONTRÔLE DES TROIS FAMILLES D'AUTO-LIEN (skill /glossaire section 5.6, refait après un premier
 * passage sur les phrases pièges synthétiques - voir plus haut) contre le contenu RÉEL de
 * production, requêtes du 2026-09-01 :
 *  - Glossaire (Dictionary) : /recherche/palette?q=arxiv renvoie 1 résultat "glossaire", mais
 *    c'est la fiche soeur "BDH-CQ" (déployée entre-temps par un autre agent du même lot), dont la
 *    définition mentionne arXiv en passant - PAS un doublon de ce terme-ci.
 *  - Annuaire (Directory) : 2 fiches existantes mentionnent "arXiv" dans un contexte correct,
 *    vérifiées par lecture directe du HTML rendu (curl) - "ml-intern" ("L'agent parcourt arXiv...",
 *    "Lecture et analyse de papiers arXiv") et "Firecrawl Research Index" ("PubMed, bioRxiv,
 *    medRxiv et arXiv..."). Les deux frontières de mot sont saines (espace ou ponctuation de part
 *    et d'autre), aucun risque de lien tronqué ou mal placé. "ml-intern" porte déjà un auto-lien
 *    actif vers /glossaire/hugging-face dans le même paragraphe, preuve que le mécanisme tourne
 *    bien sur cette page - "arXiv" y sera correctement lié vers /glossaire/arxiv une fois cette
 *    migration déployée.
 *  - Acronymes (Acronyms) : aucune section "acronymes" dans les résultats de recherche pour
 *    "arxiv" ni "arxiv.org" (confirmé sur les DEUX requêtes) ; recherche locale
 *    (Acronym::where(description like %arxiv%)) également à 0 - aucun risque identifié.
 *
 * Données dans database/data/glossaire-batch-2026-09-01-arxiv.json.
 * Anti-doublon à l'exécution : skip si le slug existe déjà, migration rejouable.
 * RÉVERSIBLE : down() supprime uniquement ce terme (aucune relation externe créée par up()).
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-09-01-arxiv.json';
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
            $term->hero_image = ! empty($t['has_image']) ? 'images/glossaire/'.$t['slug'].'.webp' : null;
            $term->reference_url = $t['reference_url'] ?? null;
            $term->reference_label = $t['reference_label'] ?? null;
            $term->is_published = true;
            $term->match_strategy = $t['match_strategy'] ?? 'loose';
            $term->sort_order = 970 + $i;
            $term->save();

            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach ($this->terms() as $t) {
            Term::where('slug->fr_CA', $t['slug'])->delete();
        }
    }
};
