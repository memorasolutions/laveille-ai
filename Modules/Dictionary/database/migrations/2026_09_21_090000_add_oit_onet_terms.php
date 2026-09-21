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
 * Deux fiches rendues necessaires par la serie « IA et emplois 2030 », qui cite l indice
 * d exposition de l OIT et s appuie sur des etudes construites a partir de la base O*NET.
 * Sans ces fiches, deux notions centrales du dossier restent des sigles opaques.
 *
 * CONTROLE ANTI-DOUBLON EXECUTE LE 2026-09-20, par FAMILLE de motifs et non par un seul mot,
 * comme l exige le skill /glossaire : 860 termes recenses depuis le sitemap (glossaire +
 * acronymes), cherches sur oit|ilo|labour|travail|organisation-internationale|
 * bureau-international d une part, onet|o-net|occupational|esco|rome|isco d autre part. Les
 * correspondances trouvees etaient toutes des faux positifs de sous-chaine (« droit-a-l-oubli »
 * contient « oit », « chrome » contient « onet », « unesco » contient « esco »). Les slugs
 * /glossaire/oit, /acronymes-education/oit, /glossaire/organisation-internationale-du-travail,
 * /glossaire/onet et /glossaire/o-net repondaient tous 404.
 *
 * RESERVE ASSUMEE, qui n a pas pu etre levee : le sitemap ne liste que des SLUGS, jamais les
 * ALIAS. Une notion peut donc etre couverte par une fiche dont le nom est tout autre - c est le
 * piege mesure le 2026-09-01, ou « double authentification » ne figurait dans aucun slug alors
 * que la fiche `2fa` la traitait. Ce qui est etabli ici : aucun SLUG ne porte ces deux notions.
 * Si un alias devait se reveler en doublon, la reponse est d enrichir la fiche existante et de
 * retirer celle-ci, jamais de laisser deux pages se cannibaliser.
 *
 * FAIT INTERCEPTE AVANT PUBLICATION, et c est la raison d etre du gate qualite : le modele
 * sollicite pour la redaction avait ecrit, en exemple pour l OIT, qu un syndicat et une
 * association patronale « peuvent porter conjointement une plainte devant l OIT ». Cette
 * affirmation ne figurait PAS dans la matiere documentaire fournie - elle a ete ecartee et
 * remplacee par un fait verifie a la source (l adoption annuelle des normes par la Conference
 * internationale du Travail). Un fait plausible ajoute par un modele traverse la delegation
 * intact et sort avec l autorite du livrable si personne ne le cherche.
 *
 * SOURCES : ilo.org pour l OIT, onetonline.org pour O*NET, consultees le 2026-09-20.
 *
 * Reversible : down() retire les deux fiches par leur slug, rien d autre n est touche.
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
                'name' => 'Organisation internationale du Travail',
                'slug' => 'oit',
                'cat_slug' => 'intelligence-artificielle',
                'definition' => 'L\'Organisation internationale du Travail (OIT) est une institution spécialisée des Nations Unies, créée en 1919 dans le cadre du traité de Versailles et devenue agence onusienne en 1946. Sa mission est de promouvoir la justice sociale, les droits au travail et le travail décent. Sa particularité tient à sa gouvernance : c\'est la SEULE agence tripartite du système des Nations Unies, où les gouvernements, les employeurs et les travailleurs participent ensemble aux décisions, plutôt que de réserver la gouvernance aux seuls États. L\'OIT produit trois types d\'instruments : des conventions, juridiquement contraignantes pour les États qui les ratifient; des protocoles, qui complètent ou précisent une convention; et des recommandations, qui ne sont pas soumises à ratification et servent d\'orientation de politique publique. Quatre objectifs structurent son action : établir et promouvoir les normes internationales du travail, favoriser l\'accès à un emploi décent, étendre la protection sociale, et renforcer le dialogue social. Elle comptait 187 États membres en 2026.',
                'analogy' => 'Une table où trois parties décident ensemble des règles du travail : l\'État, l\'employeur et le syndicat, plutôt qu\'une seule qui dicte aux autres.',
                'example' => 'La Conférence internationale du Travail, qui réunit chaque année les trois groupes, est l\'instance qui adopte les nouvelles normes internationales du travail; le Conseil d\'administration assure l\'orientation entre les sessions.',
                'did_you_know' => 'Le tripartisme de l\'OIT est unique dans tout le système des Nations Unies : nulle part ailleurs les employeurs et les travailleurs ne siègent aux côtés des États depuis 1919.',
                'one_sentence_answer' => 'L\'Organisation internationale du Travail est l\'agence des Nations Unies chargée des normes du travail, et la seule du système onusien où employeurs et travailleurs décident aux côtés des gouvernements.',
                'faq' => [
                    [
                        'question' => 'Qu\'est-ce que le tripartisme de l\'OIT, concrètement?',
                        'answer' => 'Cela signifie que trois groupes participent ensemble aux décisions : les gouvernements, qui portent les engagements internationaux des États; les employeurs, qui font valoir les réalités des entreprises; et les travailleurs, généralement représentés par les organisations syndicales. Les trois prennent part à la négociation des normes, à l\'élaboration des politiques et aux programmes de l\'organisation. C\'est une structure unique dans le système des Nations Unies, où la gouvernance est ailleurs réservée aux États.',
                    ],
                    [
                        'question' => 'Les conventions de l\'OIT s\'imposent-elles à tous les pays?',
                        'answer' => 'Non. Une convention devient juridiquement contraignante pour un État seulement s\'il la ratifie. Les recommandations, elles, ne sont jamais soumises à ratification : elles fournissent des orientations détaillées de politique publique, sans force obligatoire. Les protocoles occupent une position intermédiaire, puisqu\'ils complètent ou précisent une convention existante. Cette distinction explique qu\'une même norme puisse s\'appliquer dans un pays et pas dans son voisin.',
                    ],
                ],
                'sources' => [
                    [
                        'label' => 'Page institutionnelle « À propos de l\'OIT », qui présente le mandat, la création en 1919 et la structure tripartite',
                        'url' => 'https://www.ilo.org/about-ilo',
                        'year' => 2026,
                        'author' => 'Organisation internationale du Travail',
                    ],
                    [
                        'label' => '« Normes internationales du travail », qui distingue conventions, protocoles et recommandations',
                        'url' => 'https://www.ilo.org/international-labour-standards',
                        'year' => 2026,
                        'author' => 'Organisation internationale du Travail',
                    ],
                ],
                'aliases' => [
                    'OIT',
                    'Organisation internationale du travail',
                ],
                'broader_slugs' => [],
                'narrower_slugs' => [],
                'difficulty' => 'beginner',
                'icon' => '⚖️',
                'type' => 'acronym',
                'match_strategy' => 'exact',
                'has_image' => false,
            ],
            [
                'name' => 'O*NET',
                'slug' => 'onet',
                'cat_slug' => 'intelligence-artificielle',
                'definition' => 'O*NET, pour Occupational Information Network, est l\'infrastructure publique américaine qui décrit le contenu des métiers. Elle a remplacé le Dictionary of Occupational Titles. Financée par le département du Travail des États-Unis via l\'Employment and Training Administration, elle est développée et maintenue par le National Center for O*NET Development, et reste accessible publiquement et sans frais. Elle se compose de quatre éléments distincts : la base de données elle-même, téléchargeable et interrogeable par services web; le Content Model, qui définit les dimensions observées pour chaque métier; la classification O*NET-SOC, alignée sur la Standard Occupational Classification américaine; et O*NET OnLine, l\'interface publique de consultation. Ce qui en fait la matière première des études sur l\'automatisation, c\'est qu\'elle décrit les métiers en TÂCHES : plus de 19 000 énoncés de tâches et plus de 2 000 activités détaillées de travail, avec environ 500 notations standardisées par profession.',
                'analogy' => 'Un inventaire public de ce que les gens font vraiment au travail, tâche par tâche, plutôt qu\'une simple liste de titres d\'emploi.',
                'example' => 'Les travaux qui estiment quelle part d\'un métier une technologie pourrait automatiser partent presque tous des énoncés de tâches d\'O*NET, puis notent ces tâches une à une plutôt que le métier dans son ensemble.',
                'did_you_know' => 'Le nombre de professions varie selon l\'unité comptée : 1 016 titres dans la taxonomie O*NET-SOC 2019, 923 professions au niveau des données, plus de 900 profils consultables. D\'où des chiffres différents d\'une étude à l\'autre.',
                'one_sentence_answer' => 'O*NET est la base publique américaine qui décrit les métiers en tâches, ce qui en fait la matière première des études mesurant quelles activités une technologie pourrait automatiser.',
                'faq' => [
                    [
                        'question' => 'Pourquoi le nombre de professions d\'O*NET change-t-il d\'une étude à l\'autre?',
                        'answer' => 'Parce que la couverture dépend de l\'unité que l\'on compte. La taxonomie O*NET-SOC 2019 contient 1 016 titres de professions; le niveau des données en renseigne 923; l\'interface publique en rend plus de 900 consultables; et plus de 55 000 intitulés d\'emploi leur sont reliés. Une étude qui annonce « 900 professions » et une autre qui en annonce « plus de 1 000 » peuvent donc décrire exactement la même base. Vérifier l\'unité comptée avant de comparer deux chiffres évite une fausse contradiction.',
                    ],
                    [
                        'question' => 'Faut-il payer pour utiliser O*NET?',
                        'answer' => 'Non. O*NET est une infrastructure fédérale de données sur le marché du travail américain, accessible publiquement et sans frais. La base est téléchargeable, disponible sous forme de fichiers tabulaires, en SQL et par services web, ce qui explique qu\'on la retrouve aussi bien dans l\'orientation professionnelle que dans la recherche universitaire. Ce n\'est ni une base commerciale, ni un projet académique isolé.',
                    ],
                ],
                'sources' => [
                    [
                        'label' => '« About O*NET », qui décrit la base, le Content Model, la classification O*NET-SOC et les chiffres de couverture',
                        'url' => 'https://www.onetonline.org/help/onet/',
                        'year' => 2026,
                        'author' => 'National Center for O*NET Development',
                    ],
                    [
                        'label' => 'Centre de ressources O*NET, qui documente le financement par l\'Employment and Training Administration du département du Travail',
                        'url' => 'https://www.onetcenter.org/',
                        'year' => 2026,
                        'author' => 'National Center for O*NET Development',
                    ],
                ],
                'aliases' => [
                    'O*NET',
                    'O*NET OnLine',
                    'Occupational Information Network',
                ],
                'broader_slugs' => [],
                'narrower_slugs' => [],
                'difficulty' => 'beginner',
                'icon' => '🧰',
                'type' => 'ai_term',
                'match_strategy' => 'exact',
                'has_image' => false,
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
            $term->sort_order = 1001 + $i;
            $term->save();

            echo "[glossaire] terme ajoute : {$t['slug']}\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach ($this->terms() as $t) {
            Term::where('slug->fr_CA', $t['slug'])->delete();
            echo "[glossaire] terme retire : {$t['slug']}\n";
        }
    }
};
