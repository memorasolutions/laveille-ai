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
 * Ajout du terme « Modèle de décision » (decision model), ressorti de l'annonce de Clef et
 * Clef-flash par Cloudflare le 1er octobre 2026.
 *
 * CONTROLE ANTI-DOUBLON (2026-10-04) : relevé du plan de site de production, motif
 * decision|classif|routage|agent|modele-de-langage. Aucune fiche portant la notion. Notion
 * VOISINE et distincte de « classification » (une question à choix reste une classification,
 * mais ici l'interface accepte n'importe quel jeu de catégories sans réentraînement) : fiche
 * nouvelle, reliée par broader_slugs=['classification'].
 *
 * ALIAS : formes anglaises et la forme francophone au singulier. Le pluriel générique
 * « modèles de décision » est VOLONTAIREMENT absent : il désigne aussi la gestion et la
 * théorie de la décision, hors IA (collision de sens, décision du superviseur du 2026-10-04).
 *
 * Source primaire = fabricant, utilisée seulement pour les faits datés et chiffrés; la
 * définition est recoupée avec une source indépendante.
 *
 * Typographie québécoise (OQLF) : espace insécable U+00A0 devant chaque deux-points, aucune
 * espace avant ; ! ?, guillemets droits, aucun tiret cadratin.
 *
 * Migration idempotente (slug déjà présent = ignoré). down() retire seulement ce terme.
 */
return new class extends Migration
{
    private const NEW_SLUG = 'modele-de-decision';

    private function resolveCategoryId(string $slug): ?int
    {
        return Category::where('slug->fr_CA', $slug)->value('id')
            ?? Category::where('slug->fr', $slug)->value('id');
    }

    private function term(): array
    {
        return [
            'name' => 'Modèle de décision',
            'slug' => self::NEW_SLUG,
            'cat_slug' => 'intelligence-artificielle',
            'definition' => "Un modèle de décision est un modèle d'IA entraîné pour trancher plutôt que pour écrire. On lui transmet des données et quelques questions typées (oui ou non, choix parmi une liste, position sur une échelle), et il renvoie des réponses structurées accompagnées de probabilités, jamais un paragraphe de texte libre. Le code qui l'appelle peut alors s'en servir directement pour router un billet de soutien, déclencher une escalade ou passer la main à un humain quand la confiance est faible. Il ressemble à un classifieur, mais il accepte n'importe quel jeu de catégories sans réentraînement et peut répondre à plusieurs questions à la fois. Il se distingue aussi d'un grand modèle de langage : celui-ci génère du texte ouvert et variable d'un appel à l'autre, alors que le modèle de décision vise une sortie bornée, rapide, peu coûteuse et constante. Typesafe AI a lancé la catégorie avec Jev, puis Cloudflare a publié Clef et Clef-flash le 1er octobre 2026.",
            'analogy' => "C'est un aiguilleur de gare : il ne raconte pas le trajet, il lit le convoi et pointe la bonne voie, avec son niveau de certitude.",
            'example' => "Cloudflare a testé Clef pour classer des sites Web. Pour un domaine donné, le modèle a renvoyé 95 % de chances qu'il s'agisse d'un site de mode, 85 % de commerce en ligne et moins de 1 % d'hameçonnage, en 2,2 secondes.",
            'did_you_know' => "Le nom Clef vient de la clé musicale, qui fixe les hauteurs de notes sur la portée, et ses deux lettres de départ, CF, évoquent aussi Cloudflare. Ses poids sont publiés sous licence Apache 2.0.",
            'one_sentence_answer' => "Un modèle de décision est un modèle d'IA qui répond à des questions typées par des choix et des probabilités plutôt que par du texte libre, pour qu'un programme ou un agent puisse décider d'une action.",
            'faq' => [
                [
                    'question' => 'Quelle est la différence avec un modèle de langage comme ChatGPT?',
                    'answer' => "Un modèle de langage génère du texte ouvert, dont la forme change d'un appel à l'autre. Un modèle de décision renvoie une sortie bornée et typée, avec des probabilités, ce qui la rend facile à brancher dans un flux automatisé, plus rapide et moins coûteuse.",
                ],
                [
                    'question' => 'Est-ce la même chose qu\'un classifieur?',
                    'answer' => "C'est un proche cousin. Une question à choix reste une forme de classification. La différence tient surtout à l'interface : un seul modèle accepte n'importe quelles catégories, sans réentraînement, et traite plusieurs questions typées en une requête.",
                ],
                [
                    'question' => 'À quoi cela sert-il concrètement?',
                    'answer' => "À prendre vite des décisions programmatiques dans un flux d'agent : urgence d'un billet, équipe à saisir, catégorie d'un site ou d'un document, besoin de confier le cas à une personne.",
                ],
            ],
            'sources' => [
                [
                    'label' => 'Présentation de Clef et Clef-flash, modèles de décision ouverts de Cloudflare, publiée le 1er octobre 2026',
                    'url' => 'https://blog.cloudflare.com/clef-decision-models/',
                    'year' => 2026,
                    'author' => 'Michelle Chen, Alex Reneau et Kevin Flansburg (Cloudflare)',
                ],
                [
                    'label' => 'Analyse technique de Clef : questions typées, probabilités en retour, aucune génération de texte',
                    'url' => 'https://flaviocopes.com/clef/',
                    'year' => 2026,
                    'author' => 'Flavio Copes',
                ],
            ],
            'aliases' => ['decision model', 'decision models', 'modèle de décision'],
            'broader_slugs' => ['classification'],
            'narrower_slugs' => [],
            'difficulty' => 'intermediate',
            'icon' => '🧭',
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

        if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
            echo "[glossaire] slug deja present, skip : {$t['slug']}\n";

            return;
        }

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
        $term->dictionary_category_id = $this->resolveCategoryId($t['cat_slug'])
            ?? $this->resolveCategoryId('intelligence-artificielle');
        $term->hero_image = 'images/glossaire/'.self::NEW_SLUG.'.webp';
        $term->is_published = true;
        $term->sort_order = 1013;
        $term->save();

        echo "[glossaire] terme ajoute : {$t['slug']}\n";
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        Term::where('slug->fr_CA', self::NEW_SLUG)->delete();
        echo "[glossaire] terme retire : ".self::NEW_SLUG."\n";
    }
};
