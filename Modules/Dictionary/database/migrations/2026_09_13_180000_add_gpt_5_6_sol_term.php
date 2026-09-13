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
 * Ajout du terme « GPT-5.6 Sol » (2026-09-13), demande du fondateur : « GPT-5.6 Sol (et les
 * derives) ».
 *
 * CE QUE « LES DERIVES » DESIGNAIT, trouve a la source primaire et non suppose : la fiche de
 * securite d OpenAI ecrit mot pour mot « GPT-5.6 is a new family of three models: Sol, our new
 * flagship model; Terra, a capable lower-cost option; and Luna, our fastest and most
 * cost-efficient model. » Les derives sont donc Terra et Luna, deux membres de la MEME famille.
 *
 * DECISION DE GRANULARITE - UNE SEULE FICHE, et voici pourquoi le glossaire ne tranche pas ca
 * tout seul : il porte aujourd hui DEUX conventions opposees.
 *   - fiches DEDIEES par modele : o1, o3, whisper, sora, codex, et gpt-6-astra depuis ce matin ;
 *   - fiche ABSORBANTE : « chatgpt » porte en alias GPT-4, GPT-4o, GPT-5 et GPT-5.2, qui n ont
 *     donc aucune fiche propre.
 * Le precedent qui tranche est Gemini : « gemini-google » (la famille, avec Gemini 3, Pro et
 * Flash en alias) coexiste avec « gemini-nano » (une declinaison qui a merite sa page). La
 * convention admet donc une fiche de famille PLUS une fiche dediee quand la declinaison le
 * justifie.
 * Ici, UNE fiche couvre les trois : Terra et Luna sont nommes dans la definition et dans la FAQ,
 * et portes en alias, mais n ont pas de page propre. Motif mesure : la veille du site cite
 * « GPT-5.6 » dans 73 actualites publiees, alors que GPT-5.1 en compte 2 et GPT-5.2 une seule.
 * Trois fiches auraient produit deux pages maigres pour des modeles que personne ne cherche
 * separement.
 *
 * ANTI-DOUBLON, par famille de motifs (gpt|sol|turbo|mini|nano) contre la base de PRODUCTION, sur
 * le nom, le slug ET les alias : 10 fiches voisines, dont chatgpt, gpt, gpt-6-astra, gemini-nano.
 * AUCUNE ne couvre GPT-5.6 ni Sol. Aucun conflit d alias : « GPT-5.6 » n est porte par personne,
 * et « GPT-5.2 » reste sur chatgpt.
 *
 * SOURCES verifiees par requete reelle, avec controle du TITRE servi :
 *   deploymentsafety.openai.com/gpt-5-6 -> 200, « GPT-5.6 System Card », publiee le 9 juillet 2026
 *   deploymentsafety.openai.com/gpt-6-astra -> 200, « GPT-6 Astra System Card »
 * A RETENIR : openai.com repond 403 a tout recuperateur automatique, y compris help.openai.com et
 * openai.com/news. Le sous-domaine deploymentsafety.openai.com est la seule porte lisible, et sa
 * page d accueil liste les fiches disponibles - c est ainsi que l adresse exacte a ete trouvee,
 * « gpt-5-6 » et non « gpt-5-6-sol » comme je l avais d abord suppose (deux 404 avant de chercher
 * la liste plutot que de deviner l adresse).
 *
 * TROIS FORMULATIONS FABRIQUEES PAR LA REDACTION DELEGUEE, INTERCEPTEES ET REECRITES :
 *   - « conception de toxines » donne en exemple de risque biologique : absent de la source.
 *   - « piratage assiste » en exemple de risque cyber : absent de la source.
 *   - « evalue comme hautement risque » : la source dit « High capability », soit un niveau de
 *     CAPACITE mesure avant diffusion, pas un jugement sur la dangerosite du produit. La nuance
 *     est reprise telle quelle dans la FAQ.
 *
 * ALIAS - retenus : « GPT-5.6 Sol », « GPT-5.6 », « GPT-5.6 Terra », « GPT-5.6 Luna ». Les formes
 * composees sont sures. ECARTES d office : « Sol » seul (le sol, la note de musique, un prenom),
 * « Terra » seul (marque et toponyme), « Luna » seul (prenom courant). C est le piege du nom
 * propre contre le nom propre, que match_strategy=case_sensitive ne protege PAS - deja mesure
 * avec AMOS, municipalite du Quebec, et avec « Astra » ce matin.
 *
 * TYPOGRAPHIE : insecables posees par script selon la norme de l Office quebecois de la langue
 * francaise (insecable avant les deux-points et dans les guillemets, AUCUNE espace devant ; ! ?).
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
                'name' => 'GPT-5.6 Sol',
                'slug' => 'gpt-5-6-sol',
                'cat_slug' => 'intelligence-artificielle',
                'definition' => 'GPT-5.6 Sol est le modèle phare de GPT-5.6, qui n\'est pas un modèle unique mais une FAMILLE de trois, présentée par OpenAI le 9 juillet 2026 : Sol, le modèle phare; Terra, une option moins coûteuse; et Luna, le plus rapide et le plus économique. C\'est la confusion la plus fréquente à son sujet, le nom de version et le nom du modèle étant souvent employés l\'un pour l\'autre. Sous le cadre interne d\'évaluation d\'OpenAI, les trois sont classés au niveau de capacité « Élevé » en cybersécurité comme en risque biologique et chimique, et aucun n\'atteint ce seuil pour l\'auto-amélioration de l\'IA. La fiche de sécurité présente d\'ailleurs les performances comme une courbe selon l\'effort de raisonnement consacré au problème, plutôt que comme un score unique. Sol est le prédécesseur direct de GPT-6 Astra, lancé le 3 septembre 2026. La famille a continué d\'évoluer après son lancement, avec une série de mises à jour documentées en août 2026.',
                'analogy' => 'Comme une gamme automobile chez un même constructeur : Sol est la berline haut de gamme, Terra la compacte polyvalente, Luna la citadine économique.',
                'example' => 'Quand OpenAI a lancé GPT-6 Astra le 3 septembre 2026, c\'est à Sol qu\'elle l\'a comparé tout au long de sa fiche de sécurité, notamment sur la résistance aux contournements de garde-fous.',
                'did_you_know' => 'Selon l\'institut britannique de sécurité de l\'IA, Sol est plus facile à surveiller quand on inspecte son raisonnement que ses seules actions visibles : certains comportements ne s\'y voient pas.',
                'one_sentence_answer' => 'GPT-5.6 Sol est le modèle phare de la famille GPT-5.6 d\'OpenAI, présentée le 9 juillet 2026 aux côtés de Terra et de Luna, et remplacée par GPT-6 Astra en septembre.',
                'faq' => [
                    [
                        'question' => 'Quelle est la différence entre Sol, Terra et Luna?',
                        'answer' => 'Ce sont les trois modèles de la même famille GPT-5.6, et OpenAI les distingue par leur positionnement plutôt que par leur nature. Sol est le modèle phare, Terra l\'option capable et moins coûteuse, Luna le plus rapide et le plus économique. Les trois reçoivent le même classement de capacité en cybersécurité et en risque biologique et chimique.',
                    ],
                    [
                        'question' => 'Que signifie le niveau « Élevé » attribué à ces modèles?',
                        'answer' => 'C\'est un palier du cadre interne qu\'OpenAI utilise pour évaluer ses modèles avant diffusion. Il mesure une CAPACITÉ, pas un jugement sur la dangerosité du produit fini : un modèle classé « Élevé » dans un domaine reçoit des protections adaptées. À titre de repère, le successeur GPT-6 Astra a été classé au palier supérieur, « Critique », en cybersécurité.',
                    ],
                    [
                        'question' => 'Pourquoi présenter les performances comme une courbe plutôt qu\'un score?',
                        'answer' => 'Parce qu\'un même modèle ne répond pas pareil selon le temps de réflexion qu\'on lui laisse. OpenAI montre donc comment le résultat évolue avec l\'effort de raisonnement consacré au problème. La source explique que cela donne une image plus complète de ce que le modèle sait faire, et de ce qu\'il en coûte pour y arriver.',
                    ],
                ],
                'sources' => [
                    [
                        'label' => 'OpenAI, fiche de sécurité de GPT-5.6 (Deployment Safety Hub)',
                        'url' => 'https://deploymentsafety.openai.com/gpt-5-6',
                        'year' => 2026,
                        'author' => 'OpenAI',
                    ],
                    [
                        'label' => 'OpenAI, fiche de sécurité de GPT-6 Astra, qui compare le successeur à Sol',
                        'url' => 'https://deploymentsafety.openai.com/gpt-6-astra',
                        'year' => 2026,
                        'author' => 'OpenAI',
                    ],
                ],
                'aliases' => [
                    'GPT-5.6 Sol',
                    'GPT-5.6',
                    'GPT-5.6 Terra',
                    'GPT-5.6 Luna',
                ],
                'broader_slugs' => [
                    'openai',
                    'llm',
                ],
                'narrower_slugs' => [],
                'difficulty' => 'intermediate',
                'icon' => '☀️',
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

        $fallbackCatId = $this->resolveCategoryId('intelligence-artificielle');

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
            $term->sort_order = 983 + $i;
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
