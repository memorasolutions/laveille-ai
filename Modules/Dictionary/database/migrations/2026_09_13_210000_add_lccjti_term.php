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
 * Ajout du terme « LCCJTI » (2026-09-13), demande du fondateur a partir d un passage de ses propres
 * contenus sur les articles 44 et 45, puis « ajoute les autres importantes ».
 *
 * GRANULARITE : UNE fiche pour la LOI, pas une fiche par ARTICLE. Personne ne cherche « article 45
 * LCCJTI » isolement ; on cherche ce que la loi quebecoise exige en matiere de biometrie. Deux
 * fiches d un paragraphe chacune se seraient cannibalisees au referencement.
 *
 * « LES AUTRES IMPORTANTES » : la mesure montre que le glossaire les couvre DEJA. La famille
 * juridique compte 12 fiches publiees - ai-act, biometrie, commission-acces-information,
 * consentement-eclaire, donnees-personnelles, dpia, information-sensible, liad, loi-25,
 * regulation-ia, renseignement-personnel, rgpd. Le seul manque REEL etait la LCCJTI :
 * verifie fiche par fiche, AUCUNE des six fiches les plus proches (loi-25,
 * commission-acces-information, liad, donnees-personnelles, biometrie, renseignement-personnel) ne
 * mentionne la LCCJTI, ni son nom long, ni les articles 44 ou 45. Le lien entre la biometrie et le
 * droit quebecois n etait fait nulle part.
 * ECARTES avec motif : la Charte des droits et libertes de la personne et le Code civil du Quebec
 * ne sont pas des termes d intelligence artificielle et depassent le perimetre d un glossaire
 * techno. La LPRPDE federale n a pas de fiche propre mais est evoquee dans « liad ».
 *
 * SOURCE PRIMAIRE lue directement, pas resumee : le texte officiel sur LegisQuebec
 * (legisquebec.gouv.qc.ca/fr/document/lc/c-1.1, HTTP 200) a ete recupere et les articles 44 et 45
 * extraits de son contenu. Le passage fourni par le fondateur a ete CONTROLE contre ce texte : il
 * est exact sur les deux points (divulgation prealable a la Commission plus consentement expres
 * pour l article 44 ; divulgation de la creation d une banque au plus tard 60 jours avant la mise
 * en service pour l article 45).
 * TROIS PRECISIONS AJOUTEES, absentes du passage d origine et lues dans le texte officiel :
 *   - l article 44 impose de n utiliser que le MINIMUM de caracteristiques necessaires ;
 *   - il impose aussi de DETRUIRE tout autre renseignement decouvert au passage des que le motif
 *     de la verification n existe plus ;
 *   - l article 45 permet a la Commission de rendre des ORDONNANCES sur ces banques.
 * L article 44 porte deux references : 2001, c. 32, a. 44, puis une modification de 2021 par le
 * chapitre 25, soit la Loi 25 - d ou le rattachement de cette fiche a « loi-25 ».
 *
 * QUATRE FAUTES JURIDIQUES INTERCEPTEES dans la redaction deleguee, malgre des consignes
 * explicites, et l une d elles pouvait induire un etablissement scolaire en erreur :
 *   1. « doit d abord obtenir l APPROBATION de la Commission » - FAUX et important. La loi exige
 *      une DIVULGATION, pas une approbation. Une FAQ entiere est desormais consacree a cette
 *      distinction.
 *   2. « La loi prevoit des sanctions administratives pecuniaires » - INVENTE. Aucune source
 *      fournie ne parle de sanctions. Passage supprime.
 *   3. « La Loi 25 a modifie l article 44 pour renforcer les protections, notamment en exigeant la
 *      destruction » - je sais QUE la modification a eu lieu, pas CE qu elle a change. Attribuer
 *      l exigence de destruction a cette modification est une invention. Reecrit.
 *   4. « consentement ecrit » la ou le texte dit « consentement expres ». Corrige.
 * C est la cinquieme redaction deleguee fautive de la journee, et la plus lourde de consequences.
 *
 * PRUDENCE : la fiche cite le texte fidelement, ne se presente jamais comme un avis juridique et le
 * DIT explicitement dans sa derniere FAQ. Le lectorat comprend le milieu de l education, qui prend
 * des decisions reelles a partir de ces textes.
 *
 * ALIAS : « LCCJTI » et le nom long, en case_sensitive. ECARTES d office : « article 44 » et
 * « article 45 », chaines generiques qui apparaissent dans n importe quel texte juridique et
 * poseraient des liens faux sur tout le site.
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
                'name' => 'LCCJTI',
                'slug' => 'lccjti',
                'cat_slug' => 'securite-et-ethique',
                'definition' => 'La LCCJTI est la Loi concernant le cadre juridique des technologies de l\'information, en vigueur au Québec sous le chapitre C-1.1. Deux de ses articles encadrent la biométrie, et ce sont ceux que l\'on cite le plus souvent dans les dossiers d\'intelligence artificielle. L\'article 44 prévoit que nul ne peut exiger, sans l\'avoir divulgué préalablement à la Commission d\'accès à l\'information et sans le consentement exprès de la personne, que la vérification de son identité passe par un procédé saisissant des caractéristiques ou des mesures biométriques. Le même article impose deux exigences moins connues : n\'utiliser que le MINIMUM de caractéristiques nécessaires, et détruire tout autre renseignement découvert au passage dès que le motif de la vérification n\'existe plus. L\'article 45 vise la création d\'une banque de telles mesures, qui doit être divulguée à la Commission au plus tard 60 jours avant sa mise en service. La Commission peut en outre rendre des ordonnances sur ces banques.',
                'analogy' => 'Demander une carte d\'identité ne se compare pas à saisir une empreinte : la seconde engage le corps, et la loi exige donc de le déclarer d\'avance.',
                'example' => 'Un établissement qui voudrait contrôler l\'accès à un local par lecture de l\'iris devrait divulguer le procédé à la Commission d\'accès à l\'information et obtenir le consentement exprès de chaque personne visée.',
                'did_you_know' => 'Le délai de 60 jours court AVANT la mise en service de la banque, pas après. Une organisation qui déclare son système une fois installé est déjà hors du cadre prévu.',
                'one_sentence_answer' => 'La LCCJTI est la loi québécoise qui encadre la biométrie : ses articles 44 et 45 imposent de divulguer à la Commission d\'accès à l\'information tout procédé biométrique et toute banque d\'empreintes.',
                'faq' => [
                    [
                        'question' => 'Quel est le lien entre la LCCJTI et la Loi 25?',
                        'answer' => 'L\'article 44 porte deux références : sa version d\'origine, adoptée en 2001, et une modification apportée en 2021 par le chapitre 25 des lois de cette année, soit la loi communément appelée Loi 25. Les deux textes se lisent donc ensemble. Le détail de ce que cette modification a changé n\'est pas repris ici : il se lit directement dans le texte officiel, dont l\'adresse figure en source.',
                    ],
                    [
                        'question' => 'Quel rôle joue la Commission d\'accès à l\'information?',
                        'answer' => 'Elle est destinataire des divulgations exigées par les deux articles : celle d\'un procédé biométrique servant à vérifier une identité, et celle de la création d\'une banque de caractéristiques ou de mesures. L\'article 45 prévoit en outre qu\'elle peut rendre toute ordonnance concernant de telles banques, pour en déterminer la confection, l\'utilisation, la consultation, la communication et la conservation, y compris l\'archivage ou la destruction.',
                    ],
                    [
                        'question' => 'Divulguer, est-ce la même chose que demander une autorisation?',
                        'answer' => 'Le texte emploie le mot « divulguer », pas « faire approuver » : l\'obligation est de porter le procédé ou la banque à la connaissance de la Commission, dans les délais prévus. Cela ne signifie pas que la Commission reste sans pouvoir, puisque l\'article 45 lui permet de rendre des ordonnances sur ces banques. Cette fiche décrit le texte, elle ne remplace pas un avis juridique.',
                    ],
                ],
                'sources' => [
                    [
                        'label' => 'Loi concernant le cadre juridique des technologies de l\'information, RLRQ c. C-1.1, texte officiel sur LégisQuébec',
                        'url' => 'https://www.legisquebec.gouv.qc.ca/fr/document/lc/c-1.1',
                        'year' => 2026,
                        'author' => 'Éditeur officiel du Québec',
                    ],
                    [
                        'label' => 'Commission d\'accès à l\'information du Québec, site officiel',
                        'url' => 'https://www.cai.gouv.qc.ca/',
                        'year' => 2026,
                        'author' => 'Commission d\'accès à l\'information du Québec',
                    ],
                ],
                'aliases' => [
                    'LCCJTI',
                    'Loi concernant le cadre juridique des technologies de l\'information',
                ],
                'broader_slugs' => [
                    'biometrie',
                    'loi-25',
                ],
                'narrower_slugs' => [],
                'difficulty' => 'intermediate',
                'icon' => '📜',
                'type' => 'explainer',
                'match_strategy' => 'case_sensitive',
                'acronym_full' => 'Loi concernant le cadre juridique des technologies de l\'information',
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

        $fallbackCatId = $this->resolveCategoryId('securite-et-ethique');

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
            $term->acronym_full = $t['acronym_full'];
            $term->match_strategy = $t['match_strategy'];
            $term->dictionary_category_id = $this->resolveCategoryId($t['cat_slug']) ?? $fallbackCatId;
            $term->hero_image = ! empty($t['has_image']) ? 'images/glossaire/'.$t['slug'].'.webp' : null;
            $term->is_published = true;
            $term->sort_order = 986 + $i;
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
