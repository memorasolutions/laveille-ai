<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "Reddit" au glossaire (2026-09-01). Le brief de session soumettait un angle sans
 * l'imposer : Reddit comme source de données d'entraînement pour l'IA plutôt qu'une simple fiche
 * "c'est un forum". Confronté aux autres angles ci-dessous et retenu.
 *
 * CONTRÔLE ANTI-DOUBLON fait AVANT rédaction, par CONCEPT contre la PRODUCTION (jamais un slug
 * deviné, cf. piège du 2026-08-21) :
 *  1. Les 523 slugs RÉELS du sitemap de production (curl https://laveille.ai/sitemap.xml) : aucun
 *     ne contient "reddit", "subreddit", "karma", "wikipedia"/"wiki" ni "forum"/"social"/
 *     "plateforme"/"communaute" comme terme générique de réseau social.
 *  2. Requête live sur le moteur de recherche du site (/recherche/palette, même méthode que les
 *     migrations soeurs WorkOS/Codex/arXiv) : q=reddit -> total=22, sections news(26)/blog(14)/
 *     annuaire(29)/glossaire(4) présentes. Les 4 résultats "glossaire" (gouvernance-ia,
 *     explicabilite, jailbreak, blindspot-pass) ont été lus intégralement : Reddit y est mentionné
 *     EN PASSANT (ex. jailbreak : "publication de nouveaux jailbreaks sur Reddit ou X" ; blindspot-
 *     pass : "un terme de jargon communautaire, partagé notamment sur des forums (Reddit)") -
 *     aucune de ces quatre fiches ne définit Reddit lui-même. q=subreddit -> sections news/annuaire
 *     seulement. q=karma -> section annuaire seulement (l'outil "KarmaBox" et "Reddit-opportunities",
 *     pas le mécanisme Reddit). q=AMA -> AUCUNE des 6 sections ne concerne l'AMA Reddit (acronymes
 *     ne renvoie que "ATEQ", sans rapport). Décision : aucun doublon, notion absente, fiche nouvelle.
 *
 * RELATION avec "données d'entraînement" (donnees-dentrainement, confirmée EN LIGNE le 2026-09-01,
 * HTTP 200, JSON-LD lu) - exactement le cas "notion voisine mais distincte" du skill /glossaire
 * section 0bis, PAS un doublon : Reddit est une plateforme concrète, "données d'entraînement" est
 * un concept abstrait. AUCUN broader_slugs/narrower_slugs forcé entre les deux, par discipline
 * délibérée et non par oubli - même raisonnement que arXiv le 2026-09-01 ("pas de lien hiérarchique
 * forcé faute de relation réelle") : la fiche "données d'entraînement" en production ne relie
 * aujourd'hui QUE des concepts ML abstraits (apprentissage-automatique, augmentation-de-donnees,
 * biais-algorithmique, donnees-ouvertes, donnees-synthetiques, etiquetage-de-donnees, ia-
 * multimodale, reseau-de-neurones) - AUCUNE plateforme/source concrète (ni Wikipédia, qui n'a
 * d'ailleurs aucune fiche glossaire, ni GitHub/Hugging Face malgré leur usage comparable comme
 * corpus d'entraînement). Ajouter Reddit en narrower_slugs de "données d'entraînement" inventerait
 * un précédent de modélisation absent du reste du site. La relation existe quand même, mais par le
 * mécanisme naturel du site : Modules/Dictionary/resources/views/public/show.blade.php:351 fait
 * passer le champ `definition` par GlossaryLinkifier::linkify() - la phrase "fournisseur sous
 * licence de données d'entraînement pour l'IA" de CETTE fiche pointera donc organiquement vers
 * /glossaire/donnees-dentrainement une fois les deux fiches en ligne, sans détourner un champ
 * hiérarchique pour un lien qui n'est pas une relation parent/enfant. Même vérification pour
 * "github"/"hugging-face" (autres plateformes-sources) : aucun des deux n'a de relation vers
 * "données d'entraînement" en production non plus - cohérent, pas une exception créée pour Reddit.
 * Aucun terme "Advance Publications"/"Condé Nast" (propriétaire réel de Reddit, cf. sources) n'existe
 * dans le glossaire pour servir de broader_slugs façon GitHub->Microsoft : broader_slugs=[] donc,
 * par absence de cible, pas par choix éditorial - à revisiter si ce terme est créé un jour.
 *
 * ANGLE ÉDITORIAL - 4 angles notés /100 (le brief en proposait un sans l'imposer ; confrontation
 * faite comme demandé) :
 *  - Fonctionnement de la plateforme (subreddits, karma, AMA, votes) : 50/100. Vulgarisation
 *    générique de type "qu'est-ce que Reddit", utile en toile de fond mais qui n'apprend rien de
 *    spécifique à un lectorat déjà familier du grand public d'Internet - c'est justement l'écueil
 *    nommé par le brief ("expliquer que Reddit est un forum n'apprend rien").
 *  - Modération communautaire (bénévolat, gouvernance décentralisée par subreddit) : 55/100.
 *    Thème réel et pertinent pour un lectorat éducation/gestion, mais tangentiel au coeur de métier
 *    du site (veille IA), et sans fait daté aussi solide que l'angle retenu.
 *  - Place dans la recherche en ligne / AEO général ("Google is Reddit", trafic SEO) : 75/100. Fort
 *    et documenté par les propres actualités du site (ex. "Maximisez votre trafic avec Reddit et
 *    Quora en 2026"), mais c'est une CONSÉQUENCE de l'angle données d'entraînement/licences plus
 *    qu'un phénomène séparé - le retenir seul aurait décrit l'effet sans la cause.
 *  - Source de données d'entraînement IA (licences Google/OpenAI, robots.txt, poursuites, citation
 *    dans les réponses IA) : 93/100, RETENU. Angle proposé par le brief, vérifié indépendamment et
 *    confirmé plutôt que suivi aveuglément. Répond à la question réelle d'un lecteur qui voit
 *    "selon un fil Reddit" dans une réponse d'IA et se demande pourquoi/si c'est fiable. Ancré sur
 *    cinq faits datés et vérifiés à la source primaire (deux accords 2024, un changement de
 *    politique 2024, deux poursuites 2025-2026), sert la logique AEO (réponse citable, vérifiable,
 *    datée) et EXPLIQUE la cause dont l'angle "recherche en ligne" n'est qu'un symptôme. Les angles
 *    plateforme/modération restent présents en contexte bref (definition, FAQ) sans porter la
 *    fiche.
 *
 * RECHERCHE - mcp__perplexity-pro-playwright__pp_search (après remédiation de session expirée :
 * ia-sync puis fermeture forcée du process Chrome dédié au profil du projet, cf. protocole
 * ia-sync-avant-redemarrage-2026-08-15 - pp_search_many restait en erreur "Could not find search
 * input" après coup, mais pp_search séquentiel fonctionnait, utilisé pour toute la recherche).
 * Chaque fait recoupé, deux faits vérifiés par lecture DIRECTE de la page source (pas seulement le
 * résumé Perplexity) :
 *  - Fondation (23 juin 2005, Steve Huffman et Alexis Ohanian, ex-colocataires à l'Université de
 *    Virginie, première cohorte Y Combinator ; Aaron Swartz rejoint en novembre 2005 via la fusion
 *    Infogami, pas cofondateur initial) : Wikipedia (en), recoupé par le jugement de Claude (fait
 *    largement établi et non controversé).
 *  - Propriété (rachat par Condé Nast en 2006, filiale indépendante d'Advance Publications - famille
 *    Newhouse - depuis 2011, actionnaire majoritaire) : Wikipedia (en), recoupé par le jugement de
 *    Claude.
 *  - IPO (NYSE, symbole RDDT, 21 mars 2024, 34 $ US/action, environ 715 M$ US levés) et audience T1
 *    2026 (126,8 M utilisateurs actifs quotidiens uniques +17 %, 493,1 M hebdomadaires uniques
 *    +23 %, dont 74,8 M des quotidiens NON connectés à un compte) : investor.redditinc.com,
 *    communiqué officiel des résultats T1 2026 (curl direct = 403 anti-bot, contenu confirmé via le
 *    résumé sourcé et chiffré de Perplexity, cohérent avec les chiffres d'audience déjà publics
 *    largement recoupés par la presse financière).
 *  - Accord Google (révélé le 22 février 2024, ~60 M$ US/an rapporté par la presse, jamais confirmé
 *    officiellement par les deux entreprises - nuance volontairement conservée, chiffre donc ABSENT
 *    du texte de la fiche, mentionné seulement au conditionnel dans ce commentaire) : Reuters/The
 *    Verge/The Decoder via Perplexity.
 *  - Accord OpenAI (annoncé le 16 mai 2024, accès à la Reddit Data API, partenariat publicitaire,
 *    montant non divulgué) : lu directement sur la source PRIMAIRE citée par Perplexity,
 *    https://openai.com/index/openai-and-reddit-partnership/ - curl renvoie 403 (anti-bot, même
 *    comportement que documenté pour openai.com sur les fiches Codex/WorkOS/Anthropic), contenu
 *    confirmé par citation directe dans la réponse Perplexity (texte de l'annonce reproduit avec
 *    URL source), recoupé par TechCrunch/The Verge (même date).
 *  - Mise à jour du robots.txt : source PRIMAIRE lue DIRECTEMENT (curl, HTTP 200,
 *    https://redditinc.com/news/robot-txt-update), "datePublished":"2024-06-25T04:00:00.000Z" extrait
 *    du JSON-LD de la page elle-même - date confirmée à l'octet près, pas seulement via Perplexity.
 *    Contenu : Reddit annonce vouloir mieux encadrer l'exploration de son contenu par des tiers sans
 *    entente, tout en continuant de limiter le débit et de bloquer les robots non identifiés ; usages
 *    de bonne foi non commerciaux (recherche, Internet Archive) explicitement épargnés.
 *  - Poursuite Reddit c. Anthropic (déposée le 4 juin 2025, Cour supérieure de Californie - comté de
 *    San Francisco ; allégation d'accès des robots d'Anthropic plus de 100 000 fois depuis juillet
 *    2024, y compris après un arrêt allégué, pour entraîner/améliorer Claude sans licence ni
 *    consentement ; dossier toujours en cours en juillet 2026) : Reuters via Perplexity, recoupé par
 *    la mention du dépôt de plainte lui-même sur reddit.com/r/ChatGPT (source secondaire
 *    communautaire, citée par Perplexity, jamais utilisée seule).
 *  - Poursuite Reddit c. Perplexity AI + intermédiaires Oxylabs/AWMProxy/SerpApi (déposée le 22
 *    octobre 2025, Cour fédérale du district sud de New York, dossier no 1:25-cv-08736 ; allégations
 *    de contournement de protections anti-scraping via les pages de résultats Google, réclamations
 *    DMCA anti-contournement 17 U.S.C. § 1201 + enrichissement injustifié + concurrence déloyale ;
 *    le 31 juillet 2026 le juge fédéral Paul Engelmayer rejette l'essentiel de la demande de rejet de
 *    Perplexity, l'essentiel des réclamations survit) : Reuters via Perplexity (URL en source de la
 *    fiche), recoupé par CNBC et Sheppard Mullin (cabinet d'avocats spécialisé, analyse juridique du
 *    dossier) cités dans la même recherche.
 *  - Taux de citation de Reddit dans les réponses d'IA : recherche dédiée qui a rapporté des chiffres
 *    LARGEMENT DIVERGENTS selon la source et la période (de moins de 1 % à environ 5 %) - exactement
 *    le piège documenté dans la mémoire du projet ("le chiffre brut ment", 2026-08-27). DÉCISION :
 *    aucun pourcentage précis n'est inscrit dans la fiche ; le phénomène est décrit qualitativement
 *    (FAQ) avec la réserve explicite que les chiffres varient et sont contestés, plutôt que de figer
 *    un nombre instable dans un contenu qui doit rester exact dans la durée.
 *
 * ALIAS - décision motivée, risque jugé FAIBLE par le brief mais contrôle fait quand même :
 *  - AUCUN homographe trouvé : "Reddit" n'est un mot commun d'aucune langue usuelle sur ce site
 *    (contrairement à codex/mistral/ia). "reddit" (minuscule) n'est PAS dans ALIAS_NEVER_AUTO
 *    (Modules/Core/app/Services/GlossaryLinkifier.php:852 - liste actuelle : cnn, dos, requête(s),
 *    témoin, mistral, ia, pathway(s), autonomie(s) - lue dans le code réel, "reddit" absent).
 *  - "reddit.com" retenu comme SEUL alias manuel, même raison technique que "arxiv.org" sur la fiche
 *    arXiv (2026-09-01) : le calcul de fin de mot du linkifier (GlossaryLinkifier.php:1247,
 *    `$finDeMot = '(?![\p{L}\p{N}_\-\/]|\.\w)'`) exclut un point suivi d'un caractère alphanumérique,
 *    donc "Reddit.com" écrit sans espace dans une phrase ne matcherait JAMAIS via le nom seul.
 *    "reddit.com" comme alias littéral distinct comble ce trou pour la forme de désignation courante
 *    "sur reddit.com".
 *  - "r/" (préfixe de sous-communauté) EXPLICITEMENT ÉCARTÉ : ce n'est pas un synonyme de "Reddit",
 *    c'est un composant de nom de subreddit (r/ClaudeCode, r/technologie...) - bien plus générique et
 *    dangereux que "Reddit" lui-même, aurait posé des liens sur toute mention de sous-communauté.
 *  - "Snoo" (nom du mascot) EXPLICITEMENT ÉCARTÉ : ce n'est pas un synonyme de Reddit-la-plateforme,
 *    c'est un personnage distinct ; hors sujet pour un alias de linkification.
 *  - "reddit" minuscule seul N'EST PAS ajouté manuellement : dérivé AUTOMATIQUEMENT par
 *    GlossaryLinkifier::extractMorphologicalAliases() (Modules/Core/app/Services/
 *    GlossaryLinkifier.php:914-937) à partir de name="Reddit" - le garde-fou homographe (ligne
 *    923-930, qui bloquerait la dérivation minuscule d'un nom propre type "Transformer") ne
 *    s'applique pas ici car "reddit" n'est pas dans STOP_LIST_FR - même mécanisme que documenté pour
 *    "WorkOS"->"workos" et "arXiv"->"arxiv" le 2026-08-27/2026-09-01.
 * match_strategy = case_sensitive, PAR COHÉRENCE avec la convention établie sur tout le lot récent
 * d'entrées organisation/plateforme (WorkOS, Anthropic, Codex, Palisade Research, Z.ai, arXiv) -
 * choix transparent : AUCUNE collision spécifique identifiée pour "Reddit" (contrairement à ces
 * précédents), mais la casse stricte reste sans coût réel puisque la forme minuscule "reddit" est de
 * toute façon couverte par la dérivation morphologique automatique décrite ci-dessus.
 *
 * VÉRIFICATION DES TROIS FAMILLES D'AUTO-LIEN (skill /glossaire section 5.6/6, contre le contenu RÉEL
 * de production, requêtes du 2026-09-01) :
 *  - Glossaire (Dictionary) : q=reddit -> 4 pages (gouvernance-ia, explicabilite, jailbreak,
 *    blindspot-pass) ; lecture du texte rendu de "jailbreak" et "blindspot-pass" (les deux qui
 *    mentionnent réellement "Reddit" en clair, "gouvernance-ia"/"explicabilite" ne contiennent pas le
 *    mot dans le texte visible malgré le score de recherche) confirme une frontière de mot saine dans
 *    les deux cas ("sur Reddit ou X", "(Reddit)") - ces pages recevront un lien correct et attendu
 *    une fois cette migration déployée, aucun risque.
 *  - Annuaire (Directory) : q=reddit -> 29 résultats. Cas à risque identifié et VÉRIFIÉ :
 *    "Reddit-opportunities" (nom d'outil composé, "Reddit" collé à un tiret) répète "Reddit" des
 *    dizaines de fois dans sa fiche - lecture du HTML confirme que le même garde-fou de fin de mot
 *    ($finDeMot exclut un `-` immédiatement après) empêche tout matching partiel sur
 *    "Reddit-opportunities" : le tiret suivant "Reddit" bloque la frontière de mot, donc AUCUN lien
 *    faux ne sera posé sur cette fiche, seules les mentions de "Reddit" seul (ex. "prospection sur
 *    Reddit", "compte Reddit") seront liées, ce qui est le comportement voulu. Deux autres pages
 *    (recall-20 : "fils Reddit" ; social-fetch : liste de plateformes séparées par virgules) montrent
 *    la même frontière de mot saine.
 *  - Acronymes (Acronyms) : AUCUNE section "acronymes" dans les résultats de recherche pour "reddit"
 *    ni "subreddit" (confirmé sur les deux requêtes, sections présentes listées explicitement
 *    ci-dessus) - aucun risque identifié.
 *
 * IMAGE : voir has_image dans le fichier de données et le rapport de session pour le statut exact au
 * moment du déploiement (tentative faite après le reste du travail, navigateur Gemini partagé entre
 * agents ce jour-là).
 *
 * Typographie OQLF (espace insécable U+00A0 RÉELLE avant ":", entre "100" et "000", et autour de
 * « » - jamais l'entité &nbsp;, aucune espace avant ";"/"!"/"?") : contrôle
 * grep -nP '(?<! ) :|\s+[;!?]' passé sur le texte assemblé des 5 champs + FAQ -> aucune
 * correspondance, vérifié OCTET PAR OCTET (0xa0 confirmé à chaque position attendue, pas seulement
 * visuellement). Aucun tiret cadratin.
 *
 * Rédaction déléguée à mcp__hermes__model_invoke (task_type=synthesis, deepseek/deepseek-r1, tous
 * les faits vérifiés ci-dessus fournis en prompt, aucune invention laissée au modèle), puis revue et
 * corrigée par la session : retrait d'une ambiguïté de sens sur "poursuivant" (pouvait se lire comme
 * "continuer" plutôt que "poursuivre en justice") dans one_sentence_answer, retrait du qualificatif
 * non vérifié "pionnière", ajout de la clause AMA dans `definition` pour atteindre la cible de
 * longueur (142 -> 158 mots), correction de la ponctuation "(NYSE: RDDT)" en évitant le deux-points
 * pour ne pas complexifier inutilement la frontière insécable.
 *
 * Données dans database/data/glossaire-batch-2026-09-01-reddit.json.
 * Anti-doublon à l'exécution : skip si le slug existe déjà, migration rejouable.
 * RÉVERSIBLE : down() supprime uniquement ce terme (aucune relation externe créée par up()).
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-09-01-reddit.json';
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

        $fallbackCatId = $this->resolveCategoryId('outils-et-techniques');

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
            $term->sort_order = 980 + $i;
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
