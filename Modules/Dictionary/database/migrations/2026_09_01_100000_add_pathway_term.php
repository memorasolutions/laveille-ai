<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "Pathway (entreprise d'IA)" au glossaire (2026-09-01) - l'ENTREPRISE de Palo Alto
 * derrière les préprints BDH (Dragon Hatchling, arXiv:2509.26507, 30 septembre 2025) et BDH-CQ
 * (arXiv:2608.09888, 10 août 2026), sujet de la fiche d'actualité déjà publiée le jour même
 * (laveille.ai/actualites/sans-reflechir-a-voix-haute-ce-modele-inspire-du-cerveau-coute-jusqua-11-fois-moins-cher-quopenai).
 *
 * IDENTIFICATION SANS AMBIGUÏTÉ (mandat explicite - "Pathway" est un nom d'entreprise courant).
 * Recoupée par 3 canaux indépendants avant toute rédaction : (1) mcp__perplexity-pro-playwright,
 * plusieurs requêtes ; (2) mcp__superagent__codex, recherche web séparée avec mandat de corriger
 * toute inexactitude ; (3) lecture DIRECTE des pages officielles pathway.com/media-kit et
 * pathway.com/introducing-bdh-cq (curl navigateur, HTTP 200, contenu lu intégralement). Les trois
 * s'accordent : Pathway, siège social Palo Alto (Californie, États-Unis), racines franco-polonaises
 * (équipes Paris/Wrocław), fondée en 2021 par Zuzanna Stamirowska (CEO), Jan Chorowski (CTO, ex-
 * Google Brain), Adrian Kosowski (CSO) et Claire Nouet (COO, confirmée cofondatrice par Codex ET
 * par la page media-kit elle-même - absente d'un premier tour Perplexity qui ne nommait que 3
 * fondateurs, corrigée avant rédaction). Activité d'origine : Pathway Live Data Framework, un
 * framework de traitement de données en temps réel (ETL continu, RAG "temps réel" pour LLM).
 *
 * AU MOINS TROIS AUTRES ENTITÉS PORTENT UN NOM QUASI IDENTIQUE - la fiche lève la confusion au lieu
 * de l'ignorer (mandat explicite) :
 *  1. Google Pathways (avec un s) : infrastructure de calcul distribué qui a servi à entraîner PaLM
 *     (Pathways Language Model). Aucun lien organisationnel - confirmé séparément par Perplexity et
 *     par Codex (source : Google Research, research.google/blog/.../palm-scaling-to-540-billion...).
 *  2. Pathway Medical Inc. : plateforme de référence clinique par IA, basée à MONTRÉAL, acquise par
 *     Doximity en 2025 - trouvée en cherchant spécifiquement "Pathway AI startup nickname/how called"
 *     (jamais un fait qu'une recherche centrée sur BDH aurait fait remonter seule). Le rapprochement
 *     géographique avec le lectorat québécois du site rend cette confusion plus probable, pas moins.
 *  3. Pathway Labs (cardiologie, EchoNext, pathwaylabs.com) - mentionnée pour mémoire, moins probable
 *     de collision qu'une plateforme montréalaise mais un troisième homonyme réel.
 *
 * CONTRÔLE ANTI-DOUBLON fait AVANT rédaction, sur les 520 slugs RÉELS du sitemap de production
 * (jamais un sondage d'URL devinée) : aucun slug ne contient "pathway", "dragon-hatch", "bdh",
 * "brain-insp", "post-transformer" ni "hebbien". Vérifié aussi sur /acronymes-education/ et
 * /annuaire/ (les 3 familles que GlossaryLinkifier alimente depuis la même classe, cf. mémoire
 * projet 2026-08-28) : aucun match. Recherche élargie par CONCEPT contre la PRODUCTION (pas
 * seulement par slug) : /recherche/palette?q=pathway -> total=2, sections "news" (l'actualité BDH-CQ
 * elle-même, légitime) et "annuaire" (fiche "Debbie Rewards" - voir plus bas), AUCUNE section
 * "glossaire". Décision : notion absente, fiche nouvelle.
 *
 * L'ALIAS EST LE COEUR DU RISQUE - "pathway" est un nom anglais COURANT (voie, chemin, parcours),
 * employé aussi en biologie ("metabolic pathway") et en pédagogie ("learning pathway"). Le nom brut
 * "Pathway" a donc été activement mis à l'épreuve, PAS supposé sûr :
 *  - COLLISION RÉELLE TROUVÉE (pas hypothétique) : la fiche annuaire "Debbie Rewards" (laveille.ai/
 *    annuaire/debbie-rewards), lue en HTML rendu, contient littéralement "modules de formation
 *    (Pathway) sur la psychologie de l'argent" - "Pathway" y est le nom propre d'une fonctionnalité
 *    de coaching INTERNE à une appli de récompenses financières, sans aucun rapport avec l'entreprise
 *    IA. Même casse exacte ("Pathway", majuscule). Un terme nommé "Pathway" seul, même en
 *    case_sensitive, aurait fabriqué un faux lien ici - case_sensitive protège de la casse, jamais
 *    d'un AUTRE nom propre identique (leçon "Codex Alimentarius" du 2026-08-27, vérifiée applicable
 *    ici de la même façon).
 *  - Confirmé par lecture de GlossaryLinkifier::loadTerms() (Modules/Core/app/Services/
 *    GlossaryLinkifier.php, ~ligne 384) : c'est le champ `name` (jamais `slug`) qui alimente le
 *    matching. Qualifier `name` élimine donc le risque À LA RACINE, exactement comme "OpenAI Codex"
 *    (2026-08-27) plutôt que "Codex" seul.
 *  - DÉCISION : name = "Pathway (entreprise d'IA)" (jamais "Pathway" seul). slug reste le "pathway"
 *    court et propre pour l'URL (le slug n'entre jamais dans le matching - vérifié dans le code,
 *    précédent identique : name="OpenAI Codex" / slug="codex"). Vérifié que
 *    extractMorphologicalAliases() (même fichier, ~ligne 867) ne dérive JAMAIS un sous-segment du
 *    nom (seulement des variantes de CASSE et un pluriel de la chaîne ENTIÈRE) : aucun risque que le
 *    mécanisme auto-dérive "Pathway" tout seul depuis "Pathway (entreprise d'IA)".
 *  - ALIAS RETENU : "Pathway AI" uniquement. Testé : /recherche/palette?q=Pathway%20AI -> total=0
 *    sur toute la production (aucune collision actuelle). Chaîne de 2 mots exacte, extrêmement
 *    improbable en emploi accidentel (personne n'écrit "Pathway AI" pour désigner autre chose) ;
 *    forme informelle attestée dans la presse anglophone ("AI startup Pathway" / "AI company
 *    Pathway", jamais la marque principale mais un qualificatif courant).
 *  - ALIAS ÉCARTÉS ET POURQUOI :
 *      "Pathway" seul -> REJETÉ (collision réelle démontrée ci-dessus, 3 homonymes, mot anglais
 *      courant).
 *      "BDH" / "Dragon Hatchling" / "BDH-CQ" -> REJETÉS comme alias de l'ENTREPRISE : ce sont des
 *      notions VOISINES mais DISTINCTES (le produit/l'architecture, pas la société elle-même) - même
 *      logique que "Codex CLI"/"GitHub Copilot" écartés de la fiche "OpenAI Codex". Aucune fiche
 *      séparée n'existe encore pour BDH/Dragon Hatchling ; inventer un narrower_slugs vers un slug
 *      inexistant aurait été une relation cassée (interdit par la section 2 du skill). Mentionnés
 *      dans definition/example/FAQ en texte, sans statut d'alias.
 *      "Pathway Labs"/"Pathway Systems" -> REJETÉS, ce ne sont pas des formes de LA marque (Pathway
 *      Labs désigne une autre société, cardiologie ; Pathway Systems n'est attesté nulle part comme
 *      nom de cette entreprise).
 *  - match_strategy = case_sensitive, défense en profondeur (comme Anthropic/WorkOS/Codex du même
 *    mois) même si le nom qualifié rend une collision déjà quasi impossible.
 *
 * RECHERCHE ET VALIDATION CROISÉE (au moins 2 sources indépendantes par fait, conformément à la
 * section 0 du skill) :
 *  - pathway.com/media-kit (HTTP 200, lu intégralement) : identité, fondateurs, siège social, et le
 *    fait Łukasz Kaiser ("backed by leading investors and advisors, including Łukasz Kaiser, co-
 *    author of the Transformer").
 *  - pathway.com/introducing-bdh-cq (HTTP 200, lu intégralement) : "BDH-CQ scored 29.5% pass@2 on
 *    the public ARC-AGI-1 evaluation set at a computed inference cost of $0.00070 per task. GPT Luna
 *    5.6 (Low) scores 34.2% while costing 11 times more, even after accounting for OpenAI's 80%
 *    price cut of 5.6 Luna on July 30th." - source PRIMAIRE de tous les chiffres de la fiche.
 *  - arXiv:2608.09888 et arXiv:2509.26507 (HTTP 200) : titres et listes d'auteurs lus directement
 *    dans les meta tags "citation_title"/"citation_author"/"citation_date" de la page abs - PAS
 *    supposés. Premier auteur de BDH-CQ = Björn Engdahl (pas Kosowski, malgré ce qu'une lecture
 *    rapide du préprint de 2025 aurait pu laisser supposer).
 *  - mcp__superagent__codex (recherche web indépendante, mandat explicite de corriger toute erreur) :
 *    a confirmé les points 2, 4 et 5 du mandat, NUANCÉ le point 3 ("11 fois moins cher" est une
 *    estimation comparative DE PATHWAY, formulée comme telle dans la fiche - jamais présentée comme
 *    un fait établi par un tiers indépendant), et CORRIGÉ le point 1 (Claire Nouet manquante).
 *  - businesswire.com (annonce Dragon Hatchling, 1er octobre 2025) : contenu confirmé par citation
 *    directe dans les réponses Perplexity, mais renvoie HTTP 403 à curl (bot-blocking) - NON retenue
 *    comme URL de `sources` par prudence (même politique que Britannica/Fenwick dans les fiches
 *    soeurs du 2026-08-27), gardée seulement comme corroboration de lecture.
 *  - Financement (30 M$ US cumulé, valorisation ~500 M$ US, 11 août 2026) : recherche Perplexity
 *    multi-sources (thesaasnews, fundraiseinsider, pulse2, vestbee) - fait VÉRIFIÉ mais volontairement
 *    EXCLU du texte final de la fiche (contrainte de longueur de la définition, 150-165 mots : le
 *    résultat ARC-AGI-1/OpenAI jugé plus citable et plus proche de l'actualité du jour que la
 *    valorisation) - noté ici pour traçabilité, pas pour être ajouté sans mandat.
 *
 * ANGLE ÉDITORIAL - 3 angles soupesés (section 0 du skill) : (A) "produit" centré sur le résultat
 * BDH-CQ seul = 55/100, redondant avec l'actualité déjà publiée le même jour ; (B) "entreprise +
 * positionnement" (identité, fondateurs, activité d'origine, PUIS le produit qui la rend notable
 * aujourd'hui) = 90/100, retenu - sert un lecteur qui atterrit ici DEPUIS l'actualité et veut savoir
 * QUI est Pathway, sans redite pure ; (C) "gouvernance/sécurité" = 30/100, écarté, thème peu
 * pertinent pour cette entreprise à ce jour. Angle (B) retenu, à l'image des fiches soeurs Anthropic/
 * WorkOS/OpenAI Codex du 2026-08-27.
 *
 * RELATIONS : broader_slugs=[] et narrower_slugs=[], choix délibéré (comme WorkOS/Anthropic/Z.ai) -
 * aucun terme "startup-ia"/"laboratoire-ia" générique n'existe dans le glossaire pour servir de
 * parent, et BDH/Dragon Hatchling n'ont pas encore leur propre fiche pour servir d'enfant. Pas de
 * rétro-liaison depuis d'autres fiches existantes (contrairement au précédent Anthropic) : le seul
 * autre endroit du site qui mentionne "pathway" est la fiche annuaire "Debbie Rewards", où le mot
 * désigne autre chose (voir plus haut) - AUCUNE rétro-liaison n'y est donc appropriée.
 *
 * Typographie OQLF (espace insécable U+00A0 RÉELLE avant ':' '%' '$' et autour de «/», JAMAIS
 * d'espace avant ; ! ? - format Québec, différent de l'usage français standard) : construite par
 * script (jamais tapée à la main, pour éliminer le risque d'espace ASCII ordinaire glissée par
 * erreur), contrôle grep -nP '(?<! ) :|\s+[;!?]' passé sur le fichier de données -> aucune
 * correspondance.
 *
 * Image : public/images/glossaire/pathway.{webp,jpg} générée APRÈS cette migration (contrainte de
 * coordination : un autre agent utilisait le navigateur Gemini au moment de la rédaction), 1200x669,
 * compressée magick+cwebp, via /nanobanana (Playwright, compte stephane@memora.ca, gemini.google.com).
 * Métaphore visuelle abstraite envisagée : un dragon stylisé émergeant d'un réseau de connexions
 * neuronales lumineuses, palette teal/orange, aucun texte lisible, aucun logo réel. has_image=true
 * dès le départ, appliqué seulement si les 2 fichiers sont réellement livrés avant déploiement.
 *
 * Données dans database/data/glossaire-batch-2026-09-01-pathway.json.
 * Anti-doublon à l'exécution : skip si le slug existe déjà, migration rejouable.
 * RÉVERSIBLE : down() supprime uniquement ce terme (aucune relation externe créée par up()).
 */
return new class extends Migration
{
    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-09-01-pathway.json';
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
