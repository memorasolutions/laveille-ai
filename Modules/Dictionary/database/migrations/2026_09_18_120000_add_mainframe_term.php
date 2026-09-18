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
 * Ajout du terme « mainframe » (2026-09-18), demande du fondateur.
 *
 * ANTI-DOUBLON, par FAMILLE de motifs et non par un seul mot, contre les 549 slugs du plan de site
 * de PRODUCTION : mainframe, ordinateur-central, grand-systeme, z-os|zos|ibm-z, cobol,
 * serveur-central|unite-centrale, calculateur, legacy|patrimonial|heritage, as-400|iseries,
 * batch|traitement-par-lots. NEUF familles a zero. La dixieme a ramene un seul slug, `batch`, qui a
 * ete OUVERT et LU : il parle du lot d exemples d entrainement d un modele, pas du traitement par
 * lots d un ordinateur central. Homonyme total, faux positif ecarte. Aucun alias du glossaire ne
 * couvre la notion non plus. Fiche NOUVELLE, donc, et c est une conclusion, pas un silence.
 *
 * AUCUN PARENT HIERARCHIQUE N EXISTE, et c est mesure : ni centre-de-donnees, ni serveur, ni
 * base-de-donnees, ni haute-disponibilite, ni virtualisation ne figurent au glossaire.
 * `cloud-computing` existe mais c est un VOISIN, pas un parent : un lecteur qui cherche l un n est
 * pas satisfait par l autre. broader_slugs est donc laisse vide plutot que de fabriquer une
 * relation fausse. La fiche reste atteignable par l auto-lien, qui est son vrai chemin d entree.
 *
 * L ANGLE DE LA FICHE, et c est lui qui la distingue de toutes les definitions disponibles
 * ailleurs : les deux chiffres les plus cites au sujet des mainframes ne sont PAS des mesures.
 *   - « 87 % des transactions par carte » vient d un communique d IBM de 2017. Sa formulation
 *     d origine dit « supported by IBM Z systems », c est-a-dire PRISES EN CHARGE PAR, ce qui est
 *     beaucoup plus large que « traitees par ». La reprise courante laisse tomber la nuance. IBM
 *     lui-meme ecrit « environ 90 % » dans son rapport annuel de 2019 : le chiffre bouge sans
 *     qu aucune methode publiee ne permette de savoir pourquoi.
 *   - « 220 milliards de lignes de COBOL » est surtout attribue a une depeche de Reuters de 2017,
 *     sans methode de comptage publiee. Selon la definition retenue, la fourchette defendable va de
 *     220 a plus de 800 milliards.
 * Aucun recensement mondial independant et auditable n existe pour l un ni pour l autre. La fiche
 * le DIT, au lieu de recopier les chiffres comme s ils etaient etablis.
 *
 * UN FAIT DE MON PROPRE BRIEF A ETE DEMENTI PAR LA VERIFICATION, et l exemple a change a cause de
 * cela. J avais retenu la migration ratee de TSB en avril 2018 pour illustrer « le risque de sortir
 * d un mainframe ». Verification faite dans la notification finale de la FCA du 19 decembre 2022 :
 * elle ne qualifie JAMAIS la plateforme quittee de mainframe. Elle la nomme seulement « the LBG IT
 * Platform », sans architecture, sans fournisseur, sans systeme d exploitation. L exemple aurait
 * donc prete au terme un cas qui ne le concerne peut-etre pas. Il a ete remplace par un fait
 * AUDITE par une institution independante : le rapport GAO-16-468, qui ecrit noir sur blanc que le
 * fichier maitre des contribuables de l IRS est en langage d assemblage et tourne sur un mainframe
 * IBM. Le contraste sert d ailleurs l angle : les chiffres solides viennent des audits publics, pas
 * des communiques de fournisseurs.
 *
 * ALIAS : comptes dans le corpus PUBLIE avant d etre declares, jamais supposes.
 *   « mainframe » 5 occurrences, « mainframes » 1  -> declares.
 *   « ordinateur central » 0, « ordinateurs centraux » 0, « grand systeme » 0  -> NON declares.
 * Le nom francais figure dans la definition, ou il informe le lecteur, mais un alias a zero
 * occurrence est du bruit et non une precaution. Condition de reveil : s il apparait un jour dans
 * le corpus, l ajouter alors.
 * match_strategy `loose` (insensible a la casse) parce que « mainframe » est un nom commun ecrit en
 * minuscules dans du texte courant, sans homonyme au glossaire - contrairement aux noms de produits
 * qui exigent `case_sensitive`.
 *
 * Les trois adresses de `sources` ont ete verifiees par requete REELLE. Les deux d IBM repondent
 * 200. gao.gov renvoie 403 a un client en ligne de commande : c est un mur ANTI-ROBOT, pas une page
 * absente, et la page a donc ete ouverte au navigateur, qui a servi le titre exact attendu
 * (« Information Technology: Federal Agencies Need to Address Aging Legacy Systems | U.S. GAO »).
 *
 * Migration idempotente : un slug deja present n est pas recree. down() supprime le terme ajoute.
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
                'name' => 'Mainframe',
                'slug' => 'mainframe',
                'cat_slug' => 'outils-et-techniques',
                'definition' => 'Un mainframe, ou ordinateur central, est une machine d\'entreprise conçue pour exécuter sans interruption un très grand volume de transactions courtes : paiements, virements, mises à jour de comptes, traitements par lots de nuit. Ce qui le distingue d\'une grappe de serveurs n\'est pas la vitesse brute, mais le débit soutenu et l\'intégrité : ses composants sont redondants et remplaçables à chaud, ses chemins d\'entrée-sortie multiples, et chaque opération est soit entièrement validée, soit entièrement annulée. Les partitions logiques (LPAR) y font cohabiter des systèmes d\'exploitation distincts, comme z/OS ou Linux, isolés les uns des autres. Des circuits dédiés au chiffrement et à la compression libèrent les processeurs pour les transactions. Mainframe et infonuagique ne s\'opposent pas : le premier garde l\'état de référence, le second expose les interfaces. Attention en revanche aux chiffres qui circulent, comme les 87 % des transactions par carte ou les 220 milliards de lignes de COBOL : aucun recensement mondial indépendant ne les étaye.',
                'analogy' => 'Comme la salle des machines d\'un paquebot : invisible des passagers, elle tourne sans arrêt, et tout s\'immobilise si elle cale.',
                'example' => 'En 2016, le Government Accountability Office américain relevait que le fichier maître des contribuables du fisc fédéral, écrit en langage d\'assemblage et exécuté sur un mainframe IBM, avait environ 56 ans.',
                'did_you_know' => 'Les 87 % viennent d\'un communiqué d\'IBM de 2017, qui disait ces transactions « prises en charge par » ses systèmes IBM Z, pas traitées par eux. Le rapport annuel 2019 avançait plutôt 90 %.',
                'one_sentence_answer' => 'Un mainframe est un ordinateur central conçu pour exécuter en continu de très gros volumes de transactions critiques, avec une priorité donnée à la disponibilité et à l\'intégrité des données plutôt qu\'à la vitesse brute.',
                'faq' => [
                    [
                        'question' => 'Quelle est la différence entre un mainframe et un serveur ordinaire ou l\'infonuagique?',
                        'answer' => 'La différence n\'est pas la puissance, c\'est ce qu\'on optimise. Un parc de serveurs ou un service infonuagique vise l\'élasticité : on ajoute des machines quand la charge monte, et on accepte qu\'une machine tombe. Un mainframe vise le débit transactionnel soutenu et l\'intégrité des données, avec des composants redondants remplaçables sans interrompre le service. Les deux coexistent d\'ailleurs souvent dans la même entreprise : le mainframe garde l\'état de référence des comptes, tandis que des services distribués font tourner l\'application mobile et le site web.',
                    ],
                    [
                        'question' => 'Les chiffres de 87 % des transactions par carte et de 220 milliards de lignes de COBOL sont-ils fiables?',
                        'answer' => 'Ce sont des ordres de grandeur, pas des mesures auditées. Le premier vient d\'un communiqué d\'IBM de 2017, qui parlait de transactions « prises en charge par » ses systèmes IBM Z, une formulation plus large que « traitées par ». IBM lui-même a avancé environ 90 % dans son rapport annuel de 2019. Le second est surtout attribué à une dépêche de Reuters de 2017, sans méthode de comptage publiée; selon la définition retenue, la fourchette va de 220 à plus de 800 milliards de lignes. Pour l\'un comme pour l\'autre, il n\'existe aucun recensement mondial indépendant et vérifiable.',
                    ],
                    [
                        'question' => 'Pourquoi les mainframes n\'ont-ils pas disparu avec l\'essor de l\'infonuagique?',
                        'answer' => 'Parce que déplacer un système de référence coûte cher et se rate souvent. Le Government Accountability Office américain relevait en 2016 que le fichier maître des contribuables du fisc fédéral, écrit en langage d\'assemblage sur un mainframe IBM, avait environ 56 ans, et que l\'agence n\'avait aucune date ferme de remplacement. Ce n\'est pas de l\'immobilisme : tant que la machine tient son engagement de disponibilité et d\'intégrité, le risque de la remplacer dépasse souvent le gain attendu.',
                    ],
                ],
                'sources' => [
                    [
                        'label' => 'Communiqué de lancement d\'IBM Z, « IBM Mainframe Ushers in New Era of Data Protection », source du chiffre des 87 %',
                        'url' => 'https://uk.newsroom.ibm.com/2017-06-17-IBM-Mainframe-Ushers-in-New-Era-of-Data-Protection',
                        'year' => 2017,
                        'author' => 'IBM',
                    ],
                    [
                        'label' => 'Rapport annuel 2017 d\'IBM, qui reprend le chiffre sous la formulation « supported by IBM Z systems »',
                        'url' => 'https://www.ibm.com/investor/att/pdf/IBM_Annual_Report_2017.pdf',
                        'year' => 2017,
                        'author' => 'IBM',
                    ],
                    [
                        'label' => '« Information Technology: Federal Agencies Need to Address Aging Legacy Systems » (GAO-16-468), qui documente le fichier maître du fisc américain sur mainframe IBM',
                        'url' => 'https://www.gao.gov/products/gao-16-468',
                        'year' => 2016,
                        'author' => 'U.S. Government Accountability Office',
                    ],
                ],
                'aliases' => [
                    'mainframe',
                    'mainframes',
                ],
                'broader_slugs' => [],
                'narrower_slugs' => [],
                'difficulty' => 'intermediate',
                'icon' => '🗄️',
                'type' => 'technique',
                'match_strategy' => 'loose',
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
            $term->sort_order = 991 + $i;
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
