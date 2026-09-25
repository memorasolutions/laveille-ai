<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ajout du terme GENERAL « Routage automatique de modèles » (model routing), demande par brief
 * du 2026-09-25. Terme ressorti du scan de la fiche d'actualite 59018 (Microsoft Copilot) comme
 * non couvert par le glossaire.
 *
 * CONTROLE ANTI-DOUBLON EXECUTE LE 2026-09-25, par FAMILLE de motifs et non par un seul mot,
 * contre le plan de site de PRODUCTION (3157 URL) sur les trois familles glossaire + acronymes +
 * annuaire : rout|orchestrat|cascade|selection-mod|multi-model|aiguill|repartit|dispatch|
 * fallback|llm-router|model-router|modele|cout|prix|tarif. UN SEUL candidat de fond, ouvert et
 * LU (pas seulement son slug) : glossaire/orchestration-ia (« L'orchestration IA est le
 * processus de coordination et de gestion de multiples modèles, agents et outils d'intelligence
 * artificielle travaillant ensemble pour accomplir des flux de travail complexes »). Notion
 * VOISINE mais DISTINCTE, verifiee par le test de la section 0 bis du skill (« un lecteur qui
 * cherche l'un serait-il satisfait par l'autre? ») : l'orchestration coordonne PLUSIEURS
 * modeles/agents/outils dans un FLUX de travail, alors que le routage choisit UN SEUL modele par
 * REQUETE individuelle - un lecteur qui cherche pourquoi Copilot bascule automatiquement vers un
 * modele different pour une meme question ne trouverait pas sa reponse dans la fiche
 * orchestration-ia, centree sur la coordination multi-etapes. Conclusion : fiche NOUVELLE, reliee
 * par broader_slugs=['orchestration-ia'] (le routage est un MECANISME au service de
 * l'orchestration, pas l'inverse), avec reroutage bidirectionnel du graphe (narrower_slugs
 * d'orchestration-ia mesure et enrichi ci-dessous, jamais ecrase).
 *
 * RECHERCHE : mcp__perplexity-pro-playwright__pp_search (date du jour 2026-09-25), sources
 * officielles Microsoft (Azure/Microsoft Foundry, Microsoft 365 Copilot) et OpenRouter. Les 4
 * URLs de `sources` ont ete verifiees par requete REELLE (curl -sI -L, codes 200) juste avant
 * redaction, et leur date de publication/mise a jour reelle lue dans le HTML (meta ms.date ou
 * dateModified JSON-LD), jamais supposee.
 *
 * VALIDATION CROISEE : redaction deleguee a mcp__hermes__model_invoke (task_type=writing, sorti
 * chez moonshotai/kimi-k2.6 via la cascade OpenRouter), avec les faits de recherche transmis
 * comme CONTENU_TIERS_NON_FIABLE delimite (jamais comme instruction), interdiction explicite
 * d'inventer un fait hors de ce bloc et consigne explicite de distinguer routage et orchestration
 * en une phrase dans la definition.
 *
 * ICONE : 🛣️ (route), coherent avec la metaphore de l'aiguillage/chemin, jamais generique.
 *
 * ALIAS : formes reellement en usage dans la litterature du domaine (Microsoft Foundry, OpenRouter)
 * - toutes des locutions composees de 2 a 4 mots. AUCUN alias « routeur » isole n'est pose :
 * risque de collision avec le sens reseau/informatique courant du mot (equipement reseau), meme
 * piege que celui deja evite pour « AMOS »/toponyme sur ce projet - la prudence porte ici sur un
 * nom commun tres frequent plutot qu'un nom propre. match_strategy laisse a 'loose' (defaut) :
 * les locutions retenues sont assez composees pour ne rien capter d'etranger.
 *
 * Typographie quebecoise (OQLF) : aucune espace avant ; ! ?, aucun deux-points ni symbole $ dans
 * ce texte (donc aucune espace insecable requise), guillemets droits uniquement, aucun tiret
 * cadratin. Controle `grep -nP '(?<! ) :|\s+[;!?]'` execute sur le texte brut avant integration :
 * aucune ligne renvoyee.
 *
 * Migration idempotente : un slug deja present n'est pas recree. down() retire le terme ajoute et
 * restaure narrower_slugs d'orchestration-ia a sa valeur mesuree avant cette migration (retrait
 * du seul slug que CETTE migration y a ajoute, jamais un remplacement aveugle du tableau).
 */
return new class extends Migration
{
    private const NEW_SLUG = 'routage-automatique-de-modeles';

    private const PARENT_SLUG = 'orchestration-ia';

    private function resolveCategoryId(string $slug): ?int
    {
        return Category::where('slug->fr_CA', $slug)->value('id')
            ?? Category::where('slug->fr', $slug)->value('id');
    }

    private function term(): array
    {
        return [
            'name' => 'Routage automatique de modèles',
            'slug' => self::NEW_SLUG,
            'cat_slug' => 'outils-et-techniques',
            'definition' => "Le routage automatique de modèles est une couche de décision placée entre une application et plusieurs modèles d'IA pour diriger chaque requête vers le modèle unique le plus approprié selon la tâche, le budget et la latence. Contrairement à l'orchestration IA, qui coordonne plusieurs modèles et agents dans un flux de travail complexe, le routage choisit un seul modèle par requête. Le système analyse des signaux comme le type de tâche (résumé, code, vision), la difficulté, la longueur du contexte, les capacités requises (function calling, JSON strict) et l'état opérationnel. Son objectif est d'éviter d'utiliser un modèle coûteux pour une tâche simple en envoyant les requêtes faciles vers des petits modèles rapides et les tâches complexes vers des modèles plus performants. Microsoft Foundry propose un Model router entraîné qui opère selon les modes Balanced, Cost et Quality, tandis que Copilot Cowork utilise le réglage Auto pour choisir entre GPT-5.5 Thinking, des modèles Anthropic pour le visuel et GPT-5.5 pour la recherche approfondie.",
            'analogy' => "Un responsable d'entrepôt qui assigne chaque colis au camion le plus économique selon la distance et la fragilité.",
            'example' => 'En juin 2026, le mode Auto de Copilot Cowork choisit entre GPT-5.5 Thinking, des modèles Anthropic pour le visuel et GPT-5.5 pour la recherche approfondie avec citations.',
            'did_you_know' => "Le Model router de Microsoft Foundry ne stocke pas les invites pour router et sa décision ajoute une surcharge minimale par rapport au temps d'inférence du modèle choisi.",
            'one_sentence_answer' => "C'est une couche intelligente qui dirige chaque requête vers le seul modèle le plus adéquat parmi plusieurs options, en fonction de la tâche, du budget et de la qualité requise.",
            'faq' => [
                    [
                        'question' => 'Quelle est la différence avec le fait de choisir moi-même le modèle dans un outil d\'IA?',
                        'answer' => "Le choix manuel vous oblige à deviner le bon modèle à chaque fois, alors que le routage automatique analyse la requête en temps réel et sélectionne celui qui offre le meilleur rapport qualité-coût pour cette tâche précise.",
                    ],
                    [
                        'question' => 'Que se passe-t-il si le modèle choisi automatiquement échoue?',
                        'answer' => "Chez OpenRouter, un mécanisme de fallback bascule vers un modèle de secours issu d'une liste ordonnée si le modèle primaire échoue, que ce soit par erreur de contexte trop long, modération, limite de débit ou indisponibilité.",
                    ],
            ],
            'sources' => [
                    [
                        'label' => 'Documentation officielle du Model router de Microsoft Foundry, qui explique son fonctionnement et ses modes Balanced, Cost et Quality',
                        'url' => 'https://learn.microsoft.com/en-us/azure/foundry/openai/concepts/model-router',
                        'year' => 2026,
                        'author' => 'Microsoft',
                    ],
                    [
                        'label' => 'Documentation OpenRouter sur les mécanismes de repli entre modèles (model fallbacks) en cas d\'échec du modèle primaire',
                        'url' => 'https://openrouter.ai/docs/guides/routing/model-fallbacks',
                        'year' => 2026,
                        'author' => 'OpenRouter',
                    ],
                    [
                        'label' => 'Documentation sur les modèles disponibles et le réglage Auto de Copilot Cowork',
                        'url' => 'https://learn.microsoft.com/en-us/microsoft-365/copilot/cowork/cowork-models',
                        'year' => 2026,
                        'author' => 'Microsoft',
                    ],
                    [
                        'label' => "Annonce des nouveautés de juin 2026 dans Microsoft 365 Copilot, dont la sélection automatique de modèle dans Copilot Cowork",
                        'url' => 'https://techcommunity.microsoft.com/blog/microsoft-copilot-blog/what%E2%80%99s-new-in-microsoft-365-copilot--june-2026/4529572',
                        'year' => 2026,
                        'author' => 'Microsoft',
                    ],
            ],
            'aliases' => [
                    'model routing',
                    'LLM router',
                    'routage de modèles',
                    'routeur de modèles',
                    'sélection automatique de modèle',
            ],
            'broader_slugs' => [self::PARENT_SLUG],
            'narrower_slugs' => [],
            'difficulty' => 'intermediate',
            'icon' => '🛣️',
            'type' => 'ai_term',
            'match_strategy' => 'loose',
        ];
    }

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! class_exists(Term::class) || ! class_exists(Category::class)) {
            echo "[glossaire] modele Term/Category absent, ignore\n";

            return;
        }

        $t = $this->term();
        $fallbackCatId = $this->resolveCategoryId('intelligence-artificielle');

        if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
            echo "[glossaire] slug deja present, skip : {$t['slug']}\n";
        } else {
            $term = new Term();

            foreach (['name', 'slug', 'definition', 'analogy', 'example', 'did_you_know', 'one_sentence_answer'] as $tf) {
                $term->setTranslations($tf, ['fr_CA' => $t[$tf], 'fr' => $t[$tf]]);
            }

            $term->faq = $t['faq'];
            $term->sources = $t['sources'];
            $term->aliases = $t['aliases'];
            $term->broader_slugs = $t['broader_slugs'];
            $term->narrower_slugs = $t['narrower_slugs'];
            $term->difficulty = $t['difficulty'];
            $term->icon = $t['icon'];
            $term->type = $t['type'];
            $term->match_strategy = $t['match_strategy'];
            $term->dictionary_category_id = $this->resolveCategoryId($t['cat_slug']) ?? $fallbackCatId;
            // Illustration validee par controle oracle en aveugle (deux familles de modeles independantes,
            // 2 questions fermees chacun) le 2026-09-25 : aucun texte, aucun logo, aucune
            // personne, metaphore devinee correctement sans indice ("routage ou prise de decision
            // automatique entre plusieurs chemins possibles").
            $term->hero_image = 'images/glossaire/routage-automatique-de-modeles.webp';
            $term->is_published = true;
            $term->sort_order = 1012;
            $term->save();

            echo "[glossaire] terme ajoute : {$t['slug']}\n";
        }

        // Enrichissement du graphe : ajoute ce terme aux enfants d'orchestration-ia SANS ecraser
        // les enfants deja presents. Idempotent (in_array avant ajout).
        $parent = Term::where('slug->fr_CA', self::PARENT_SLUG)->first();
        if ($parent) {
            $narrower = is_array($parent->narrower_slugs) ? $parent->narrower_slugs : [];
            if (! in_array(self::NEW_SLUG, $narrower, true)) {
                $narrower[] = self::NEW_SLUG;
                $parent->narrower_slugs = array_values($narrower);
                $parent->save();
                echo "[glossaire] narrower_slugs enrichi sur " . self::PARENT_SLUG . " : + " . self::NEW_SLUG . "\n";
            }
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        // Retire CE terme des enfants d'orchestration-ia (jamais un remplacement aveugle du
        // tableau : seul le slug ajoute par cette migration est enleve).
        $parent = Term::where('slug->fr_CA', self::PARENT_SLUG)->first();
        if ($parent) {
            $narrower = is_array($parent->narrower_slugs) ? $parent->narrower_slugs : [];
            if (in_array(self::NEW_SLUG, $narrower, true)) {
                $parent->narrower_slugs = array_values(array_filter(
                    $narrower,
                    static fn ($v) => $v !== self::NEW_SLUG
                ));
                $parent->save();
                echo "[glossaire] narrower_slugs restaure sur " . self::PARENT_SLUG . " : - " . self::NEW_SLUG . "\n";
            }
        }

        Term::where('slug->fr_CA', self::NEW_SLUG)->delete();
        echo "[glossaire] terme retire : " . self::NEW_SLUG . "\n";
    }
};
