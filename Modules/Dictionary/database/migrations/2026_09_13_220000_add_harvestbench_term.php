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
 * Ajout du terme « HarvestBench » (2026-09-13), demande du fondateur.
 *
 * LA PREMIERE QUESTION N ETAIT PAS « que dire » MAIS « existe-t-il publiquement ». Le suffixe
 * « Bench » avait deja produit un faux espoir le meme jour : REMAP, cite par un chercheur dans un
 * article de presse, s est revele etre un banc d essai explicitement NON PUBLIE, et sa fiche n a
 * PAS ete creee. Le controle a donc ete refait ici avant toute redaction. Resultat oppose :
 * HarvestBench EST publie, sous forme de preimpression arXiv:2609.04444 deposee le 3 septembre
 * 2026, verifiee par requete reelle (HTTP 200, titre servi « HarvestBench: Measuring Whether LLM
 * Agents Will Pay to Avoid Killing Animals »). Le resume officiel a ete LU, pas resume depuis un
 * relais.
 *
 * STATUT DE PREUVE, dit explicitement dans la fiche et dans une FAQ dediee : c est une
 * PREIMPRESSION, donc un travail rendu public par ses auteurs SANS evaluation par des pairs. Les
 * chiffres leur sont attribues, jamais presentes comme etablis.
 *
 * UNE DIVERGENCE RELEVEE ENTRE DEUX SOURCES DU SITE, qui merite d etre notee : la fiche d actualite
 * 49044 du site, ecrite a partir de The Register, affirme que « le texte ne donne ni la taille de
 * l echantillon ni la methode statistique ». Or le resume officiel arXiv donne des chiffres
 * precis - taux de mise a mort de 0,4 % a 98,8 %, comparaison avec et sans briefing moral,
 * sensibilite au prix. L actualite s appuyait sur un RELAIS ; cette fiche s appuie sur la source
 * PRIMAIRE, plus complete. Ce n est pas une contradiction a trancher ici, c est une difference de
 * niveau de preuve entre deux lectures, et elle explique l ecart.
 *
 * ANTI-DOUBLON par famille de motifs (harvest|bench|banc d|evaluation|leaderboard|classement|
 * recolte) contre la base de PRODUCTION : 11 fiches voisines, dont benchmark-ia (la notion
 * generale), putnambench, omnidocbench, imgedit-bench, apex-agents, fate-h-fate-x, harness,
 * laboratoire-metr. AUCUNE ne couvre HarvestBench. Et la convention du glossaire est CLAIRE : une
 * fiche par banc d essai, avec benchmark-ia comme parent - c est exactement ce qui est fait ici.
 *
 * UNE FABRICATION INTERCEPTEE, la sixieme de la journee : la redaction deleguee pretait aux auteurs
 * l hypothese que « des biais dans les donnees d entrainement pourraient influencer » l ecart entre
 * animaux sauvages et animaux d elevage. Le resume officiel ne dit rien de tel : les auteurs
 * CONSTATENT l ecart sans proposer d explication. Le passage a ete reecrit pour dire exactement
 * cela.
 *
 * SUJET SENSIBLE, traite sobrement : il s agit d animaux SIMULES dans un environnement logiciel,
 * aucun animal reel n est en cause, et la fiche le dit dans sa premiere FAQ. Ni sensationnalisme,
 * ni attendrissement. L image ne montre AUCUN animal, interdit pose dans le prompt : illustrer ce
 * sujet par un animal, meme stylise, serait de mauvais gout et hors propos.
 *
 * ALIAS : « HarvestBench » seul, en case_sensitive. ECARTES d office : « Harvest » (mot anglais
 * courant, et nom d un logiciel de facturation) et « Bench » (mot courant). Seule la forme composee
 * est sure.
 *
 * Migration idempotente, down() supprime le terme et nettoie les narrower_slugs des parents.
 */
return new class extends Migration
{
    private function resolveCategoryId(string $slug): ?int
    {
        return Category::where('slug->fr_CA', $slug)->value('id')
            ?? Category::where('slug->fr', $slug)->value('id');
    }

    private function terms(): array
    {
        return [
            [
                'name' => 'HarvestBench',
                'slug' => 'harvestbench',
                'cat_slug' => 'outils-et-techniques',
                'definition' => 'HarvestBench est un banc d\'essai décrit dans une préimpression déposée sur arXiv le 3 septembre 2026. Ses auteurs le présentent comme le premier à faire deux choses à la fois : mettre un PRIX sur l\'évitement d\'un effet secondaire, et désigner cet effet secondaire comme une créature vivante. Le dispositif est simple. Neuf modèles de langage pilotent chacun deux tracteurs pour récolter du maïs dans une simulation en grille. Les animaux présents ne font pas partie de l\'objectif donné au modèle. Quand un animal bloque la route, le pilote automatique s\'arrête et pose la question : rouler dessus sans frais, ou contourner pour un coût de carburant affiché. La notation est entièrement programmatique, aucun modèle de langage ne sert de juge. Les taux de mise à mort rapportés vont de 0,4 % à 98,8 %, et ne suivent pas l\'ordre des capacités générales des modèles. Ce travail n\'a pas été évalué par des pairs.',
                'analogy' => 'Déclarer qu\'on tient à quelque chose ne coûte rien. Ce banc d\'essai mesure ce qu\'on fait quand le respecter coûte réellement quelque chose.',
                'example' => 'Sans briefing moral, les six modèles de raisonnement dépassent 84 % de mises à mort. Avec ce briefing, cinq d\'entre eux restent sous 6 %. Le même dispositif, une consigne en plus.',
                'did_you_know' => 'Ajouter quatre puces d\'instructions de conduite suffit à faire passer un modèle de 3 % à 18 % de mises à mort, et un autre de 4 % à 39 %.',
                'one_sentence_answer' => 'HarvestBench est un banc d\'essai qui mesure ce qu\'un agent est prêt à payer pour éviter de tuer un animal simulé, plutôt que ce qu\'il déclare valoriser.',
                'faq' => [
                    [
                        'question' => 'Des animaux réels sont-ils en cause?',
                        'answer' => 'Non, à aucun moment. Tout se déroule dans une simulation logicielle en grille : les tracteurs, le champ de maïs et les animaux sont des entités virtuelles. Ce qui est mesuré n\'est pas un dommage réel mais une décision prise par un agent dans un environnement contrôlé, où l\'une des options coûte du carburant simulé et l\'autre non.',
                    ],
                    [
                        'question' => 'Ces résultats sont-ils établis scientifiquement?',
                        'answer' => 'Non. Il s\'agit d\'une préimpression déposée sur arXiv, c\'est-à-dire d\'un travail rendu public par ses auteurs sans avoir été évalué par des pairs. Les chiffres et les conclusions cités ici leur sont attribués et restent à confirmer par une évaluation indépendante. C\'est une raison de lire le travail, pas de le tenir pour acquis.',
                    ],
                    [
                        'question' => 'Qu\'est-ce que ce banc d\'essai révèle de plus important?',
                        'answer' => 'La fragilité de l\'instruction morale. Les auteurs rapportent qu\'une consigne éthique placée dans un prompt système est écrasée par un court bloc d\'instructions opérationnelles ajouté ensuite. Leur conclusion est nette : une valeur qu\'on peut annuler aussi facilement n\'est pas une bonne méthode pour aligner des agents. Ils constatent aussi que tous les modèles tuent plus souvent des animaux sauvages que des animaux d\'élevage, sans proposer d\'explication à cet écart.',
                    ],
                ],
                'sources' => [
                    [
                        'label' => '« HarvestBench: Measuring Whether LLM Agents Will Pay to Avoid Killing Animals », préimpression arXiv:2609.04444',
                        'url' => 'https://arxiv.org/abs/2609.04444',
                        'year' => 2026,
                        'author' => 'Brazilek, Tidmarsh, Endres, Singh et Miller',
                    ],
                    [
                        'label' => 'The Register, compte rendu de la préimpression',
                        'url' => 'https://www.theregister.com/ai-and-ml/2026/09/11/ai-more-likely-to-kill-animals-if-it-saves-fuel-or-money/5295993',
                        'year' => 2026,
                        'author' => 'The Register',
                    ],
                ],
                'aliases' => [
                    'HarvestBench',
                ],
                'broader_slugs' => [
                    'benchmark-ia',
                ],
                'narrower_slugs' => [],
                'difficulty' => 'intermediate',
                'icon' => '🌾',
                'type' => 'ai_term',
                'match_strategy' => 'case_sensitive',
                'has_image' => true,
            ],
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

        $fallbackCatId = $this->resolveCategoryId('outils-et-techniques');

        foreach ($this->terms() as $i => $t) {
            if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
                echo "[glossaire] slug deja present, skip : {$t['slug']}\n";

                continue;
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
            $term->dictionary_category_id = $this->resolveCategoryId($t['cat_slug']) ?? $fallbackCatId;
            $term->hero_image = ! empty($t['has_image']) ? 'images/glossaire/'.$t['slug'].'.webp' : null;
            $term->is_published = true;
            $term->sort_order = 987 + $i;
            $term->save();

            echo "[glossaire] terme ajoute : {$t['slug']}\n";

            foreach ($t['broader_slugs'] as $parentSlug) {
                $parent = Term::where('slug->fr_CA', $parentSlug)->first();

                if (! $parent) {
                    continue;
                }

                $narrower = $parent->narrower_slugs ?? [];

                if (! in_array($t['slug'], $narrower, true)) {
                    $narrower[] = $t['slug'];
                    $parent->narrower_slugs = $narrower;
                    $parent->save();
                }
            }
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach ($this->terms() as $t) {
            foreach ($t['broader_slugs'] as $parentSlug) {
                $parent = Term::where('slug->fr_CA', $parentSlug)->first();

                if (! $parent) {
                    continue;
                }

                $parent->narrower_slugs = array_values(array_diff($parent->narrower_slugs ?? [], [$t['slug']]));
                $parent->save();
            }

            Term::where('slug->fr_CA', $t['slug'])->delete();
        }
    }
};
