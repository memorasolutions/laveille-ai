<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme "Google Antigravity" au glossaire (2026-08-27) : la plateforme de développement
 * agentique de Google (IDE + gestionnaire d'agents + CLI `agy`), lancée en préversion publique le
 * 18 novembre 2025.
 *
 * SENS RETENU (homographe à trois têtes, tranché AVANT rédaction) : ce site est une veille IA, donc
 * seul le PRODUIT Google mérite une fiche dédiée - ni le sens physique (« antigravité »), ni la
 * blague `import antigravity` du langage Python (module réel depuis 2008, mais un easter egg, pas
 * un sujet de veille IA). Les deux autres sens sont mentionnés dans la FAQ pour désambiguïser le
 * lecteur, jamais fusionnés dans la définition.
 *
 * CONTRÔLE ANTI-DOUBLON fait AVANT rédaction, sur les 510 slugs RÉELS extraits du sitemap de
 * production (jamais en sondant une URL devinée) : motifs cherchés "gravity"/"antigrav" (aucun
 * résultat), "ide$"/"-ide-"/"coding-agent"/"coding-assistant"/"cursor"/"windsurf"/"copilot"/
 * "claude-code"/"cline"/"agentic" (aucun résultat pertinent - seuls "vibe-coding", "genai-divide"
 * et "metier-hybride" contiennent "agentic" en sous-chaîne, notions distinctes), "gemini" (existe
 * sous gemini-google et gemini-nano - modèle IA différent, pas la plateforme). Décision : aucun
 * doublon, nouvelle fiche. Terme parent trouvé : "google" (existe, HTTP 200 vérifié) → broader_slugs.
 *
 * NOMMAGE ET ALIAS - piège du qualifier « X (Fabricant) » ÉVITÉ DÉLIBÉRÉMENT. Le nom n'est PAS
 * "Antigravity (Google)" (qui aurait suivi le pattern de gemini-google) : GlossaryLinkifier::
 * extractQualifierAliases() dérive la BASE d'un nom "X (Y)" de façon INCONDITIONNELLE, avant même
 * de vérifier si Y est un fabricant (QUALIFIER_ORGANISATION ne bloque que le qualifier, jamais la
 * base). Avec ce nom, "Antigravity" seul serait donc devenu un alias auto-dérivé malgré tout - même
 * défaut que documenté sur "Jan (jan.ai)" dans la migration 2026_08_27_090000, et exactement le
 * risque signalé dans le mandat : "Antigravity" est un homographe à trois têtes (physique, module
 * Python, produit Google), un alias générique poserait de faux liens sur tout futur article parlant
 * d'antigravité ou de l'easter egg Python. Solution retenue (identique à "Jan.ai" dans la migration
 * citée) : name="Google Antigravity" SANS parenthèses (la regex `^(.+?)\s*\(([^)]+)\)\s*$` ne
 * matche pas, donc aucune base n'est dérivée), + match_strategy=case_sensitive.
 * ALIAS RETENU : "Antigravity IDE" (nom de produit officiel à deux mots, antigravity.google/
 * product/antigravity-ide, risque de collision quasi nul).
 * ALIAS ÉCARTÉS : "Antigravity" seul (raison ci-dessus - homographe à trois sens, jamais retenu même
 * en case_sensitive, un article futur sur la physique ou le module Python l'écrirait avec la même
 * casse). "agy" (3 caractères, nom de la commande CLI - gardé dans `example` comme demandé plutôt
 * qu'en alias, bien trop court et générique pour matcher sans faux positifs).
 *
 * RECHERCHE ET SOURCES - pp_search absent de cette session (repli documenté utilisé :
 * mcp__openrouter__chat_with_model, modèle perplexity/sonar-pro, en 3 requêtes pour couvrir
 * positionnement/date, pricing/comparaison, et vérifier spécifiquement l'existence de la commande
 * `agy`). Chaque URL retenue vérifiée par curl direct (HTTP 200) ET par lecture du contenu réel de
 * la page (extraction de texte, pas seulement le code de statut) :
 *  - https://antigravity.google/blog/introducing-google-antigravity (source primaire, Google) :
 *    "From today, Google Antigravity is available in public preview at no charge, with generous
 *    rate limits on Gemini 3 Pro usage" ; confirme Gemini 3, Claude Sonnet 4.5, GPT-OSS comme
 *    modèles disponibles "within the agent, offering developers model optionality".
 *  - https://developers.googleblog.com/build-with-google-antigravity-our-new-agentic-development-platform/
 *    (source primaire, Google, JSON-LD lu : datePublished="2025-11-20", author="Google Antigravity
 *    Team") : reprend les mêmes faits, confirme la date du 20 novembre 2025 pour ce second billet.
 *  - https://antigravity.google/docs/cli/getting-started (source primaire, Google) : contenu HTML
 *    récupéré et parsé (pas seulement code 200) - confirme littéralement "By default, the installer
 *    registers the agy binary" et "execute the launcher command: agy". La commande `agy` (mentionnée
 *    dans les règles de ce poste de travail comme « Antigravity CLI ») est donc RÉELLE et OFFICIELLE,
 *    pas une supposition ni un nom communautaire.
 *  - https://en.wikipedia.org/wiki/Google_Antigravity (source secondaire indépendante) : confirme le
 *    18 novembre 2025 comme date d'annonce dominante dans le texte, et la description "software
 *    development platform... consists of a chat oriented development environment, an IDE, a CLI,
 *    and an SDK".
 *  Non recoupé/incertain, signalé honnêtement : le détail des paliers de tarification entreprise
 *  (Gemini Enterprise Agent Platform) n'a pas de grille de prix publique retrouvée - la fiche reste
 *  donc au niveau "gratuit en préversion pour les particuliers", seul fait vérifié avec certitude.
 *  Aucune comparaison nommée officielle avec Cursor/Windsurf/Copilot n'existe dans les pages
 *  officielles consultées ; la fiche n'affirme donc PAS que Google positionne Antigravity contre
 *  ces outils par leur nom (ce serait une inférence, pas une citation).
 *
 * broader_slugs=["google"] (le terme "google" existe et sert la même fonction que sur gemini-google
 * pour Gemini). narrower_slugs=[] : rien au glossaire n'est un sous-concept d'Antigravity lui-même.
 *
 * Typographie OQLF appliquée sur le fichier de données (espace insécable U+00A0 réelle avant ':' et
 * autour de « » ; AUCUNE espace, même insécable, avant ;!? - erreur initiale corrigée : une NBSP
 * avait été posée par réflexe devant "?" dans les questions FAQ, retirée après relecture du contrôle
 * grep) - contrôle `grep -nP '(?<! ) :|\s+[;!?]'` passé (aucune correspondance) sur le fichier final.
 *
 * IMAGE PRODUITE (2026-08-27, session ultérieure) : has_image=true. Illustration générée via le
 * compte Gemini de l'utilisateur (skill /nanobanana, Playwright), 3D isométrique teal/orange, sans
 * texte ni logo - agent IA flottant entouré de trois panneaux holographiques (éditeur, terminal,
 * navigateur). Fichiers déposés AVANT cette mise à jour : public/images/glossaire/
 * google-antigravity.{webp,jpg} (1200x669, compressés, sous 90 Kio chacun).
 *
 * Données dans database/data/glossaire-batch-2026-08-27-google-antigravity.json.
 * Anti-doublon à l'exécution : skip si le slug existe déjà, migration rejouable.
 * RÉVERSIBLE : down() supprime UNIQUEMENT le slug ajouté ici.
 */
return new class extends Migration
{
    private const SLUGS = ['google-antigravity'];

    private function terms(): array
    {
        $path = __DIR__.'/../data/glossaire-batch-2026-08-27-google-antigravity.json';
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
            $term->sort_order = 960 + $i;
            $term->save();

            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach (self::SLUGS as $slug) {
            Term::where('slug->fr_CA', $slug)->delete();
        }
    }
};
