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
 * Ajout du terme « NameTag » (2026-09-13), demande du fondateur.
 *
 * SUJET LE PLUS DELICAT DE LA SERIE : reconnaissance faciale, empreintes faciales, et une action
 * collective EN COURS. La fiche est ecrite sous contrainte juridique stricte.
 *
 * LA REGLE DE PRUDENCE APPLIQUEE, et elle n est pas negociable : une plainte est un acte de
 * procedure depose par une PARTIE, jamais une decision de tribunal. A ce stade aucun fait n est
 * etabli par un jugement et aucune classe n a ete certifiee. Tout ce qui vient de la plainte est
 * donc ecrit comme une ALLEGATION (« elle REPROCHE a Meta »), jamais comme un fait. La fiche
 * n affirme nulle part que Meta a fait ce qui lui est reproche, ne dit nulle part qu un tribunal a
 * tranche, et ne nomme AUCUN plaignant - la plainte est portee par des residents de l Illinois et
 * de la Californie, dont des enfants, qui n ont pas a etre nommes dans un glossaire.
 * La position de Meta est rapportee pour l equilibre : l entreprise soutient que rien n a ete livre
 * aux consommateurs et qu aucune decision finale de lancement n avait ete prise.
 *
 * TROIS FABRICATIONS INTERCEPTEES DANS LA REDACTION DELEGUEE, et sur un sujet juridique elles
 * etaient graves :
 *   1. « Meta conteste fermement ces accusations » - PUREMENT INVENTE. Aucune source consultee ne
 *      dit quoi que ce soit de la reponse de Meta a cette poursuite. Retire.
 *   2. « ce qui est corrobore par les analyses techniques » a propos de la non-activation - une
 *      affirmation de corroboration que rien ne soutient. Retire.
 *   3. « Cela souleve toutefois des questions sur les intentions et les tests internes » - une
 *      insinuation, sur un sujet ou une insinuation n a pas sa place. Retire.
 * C est la troisieme fois de la journee qu une redaction deleguee fabrique un detail plausible ;
 * c est la premiere fois que le detail fabrique portait un risque juridique.
 *
 * ANTI-DOUBLON par famille de motifs contre la base de PRODUCTION (nametag|name tag|reconnaissance
 * faciale|biometr|empreinte|lunettes|ray-ban|vie privee|loi 25|bipa|surveillance) : 3 fiches
 * voisines existent - biometrie, loi-25, reconnaissance-faciale. AUCUNE ne couvre NameTag, qui est
 * un systeme precis et non une notion generale. Rattachee par broader_slugs a reconnaissance-faciale
 * et biometrie, ses deux parents naturels.
 *
 * SOURCES verifiees par requete reelle avec controle du TITRE servi :
 *   wired.com/story/meta-removes-face-recognition-code-meta-ai-app-smart-glasses/ -> 200,
 *     « Meta Deletes Face-Recognition System From Its Smart Glasses App After WIRED Report »
 *   courtlistener.com/docket/74753032/alvarez-v-meta-platforms-inc/ -> 200,
 *     « Alvarez v. Meta Platforms, Inc., 1:26-cv-10773 » - le dossier judiciaire lui-meme
 * Le site avait deja verifie ces sources pour sa fiche d actualite 48782 du 11 septembre, dont les
 * notes portent la meme mise en garde : « C est un acte de procedure d une partie, pas une decision
 * d un tribunal. »
 *
 * ALIAS : « NameTag » seul, en case_sensitive. La forme en deux mots « name tag » (etiquette de nom
 * en anglais) n est PAS retenue - elle apparaitrait dans des textes sans aucun rapport.
 *
 * IMAGE : aucun visage, aucun oeil, aucune silhouette humaine, interdits poses DANS le prompt et
 * non seulement au controle. Illustrer un article sur la collecte d empreintes faciales par un
 * visage, meme entierement synthetique, serait exactement ce qu il ne faut pas faire.
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
                'name' => 'NameTag',
                'slug' => 'nametag',
                'cat_slug' => 'securite-et-ethique',
                'definition' => 'NameTag est un système de reconnaissance faciale de Meta qui n\'a JAMAIS été mis à la disposition du public. C\'est la nuance que la plupart des comptes rendus escamotent. Il a été découvert en juin 2026 dans le code de l\'application compagnon Meta AI, celle qui accompagne les lunettes connectées Ray-Ban Meta et Oakley Meta, une application téléchargée sur plus de 50 millions d\'appareils. Le code était présent mais inactif : WIRED le décrit comme un système fonctionnel, mais dormant. Selon l\'analyse rapportée, il devait détecter un visage dans le flux de la caméra, le convertir en empreinte faciale, comparer cette empreinte à celles conservées sur le téléphone du porteur, puis signaler une correspondance. Il devait aussi garder localement les visages non reconnus en vue d\'un traitement ultérieur, ce qui soulevait la question de l\'origine des empreintes servant à identifier une personne inconnue. Meta soutient que rien n\'a été livré aux consommateurs et qu\'aucune décision finale de lancement n\'avait été prise.',
                'analogy' => 'Comme un système de surveillance entièrement installé dans un immeuble, câblé et prêt, mais dont personne n\'a jamais actionné l\'interrupteur.',
                'example' => 'Le 4 juin 2026, WIRED révèle la présence de ce code inactif dans l\'application. Le lendemain, une mise à jour retire les bibliothèques de reconnaissance faciale.',
                'did_you_know' => 'Le code de reconnaissance faciale dormait dans une application installée sur plus de 50 millions d\'appareils, sans qu\'aucun de leurs propriétaires n\'ait eu de raison de s\'en douter.',
                'one_sentence_answer' => 'NameTag est un système de reconnaissance faciale de Meta, jamais activé pour le public, découvert en juin 2026 dans le code d\'une application de lunettes connectées puis retiré le lendemain.',
                'faq' => [
                    [
                        'question' => 'Quelle différence entre « le code était là » et « la fonction était active »?',
                        'answer' => 'Une fonction peut être écrite, intégrée à une application et livrée sur des millions de téléphones sans être accessible à qui que ce soit : c\'est le cas décrit ici. Meta affirme que rien n\'a été livré aux consommateurs et qu\'aucune décision finale de lancement n\'avait été prise. WIRED relève toutefois qu\'affirmer qu\'une fonction inaccessible « n\'existait pas » relève de la formulation plus que de la technique, puisque le code, lui, était bien là.',
                    ],
                    [
                        'question' => 'Que reproche l\'action collective déposée en septembre 2026?',
                        'answer' => 'Une action collective proposée a été déposée le 4 septembre 2026 devant le tribunal fédéral du district nord de l\'Illinois, à Chicago, par des résidents de l\'Illinois et de la Californie. Sur 66 pages, elle REPROCHE à Meta d\'avoir récupéré des photos publiées, sans avis ni consentement adéquat, pour entraîner ses systèmes. Ce sont des allégations : à ce stade aucun fait n\'a été établi par un jugement, et aucune classe n\'a été certifiée.',
                    ],
                    [
                        'question' => 'Le projet est-il abandonné?',
                        'answer' => 'Les bibliothèques ont été retirées de l\'application dès le lendemain de la révélation, mais ce n\'est pas la même chose qu\'un abandon. Meta qualifie le projet d\'exploratoire et n\'a pas déclaré y renoncer définitivement. Autrement dit, le code a disparu de la version distribuée, la possibilité d\'une fonction future n\'a pas été écartée publiquement.',
                    ],
                ],
                'sources' => [
                    [
                        'label' => 'WIRED, « Meta Deletes Face-Recognition System From Its Smart Glasses App After WIRED Report »',
                        'url' => 'https://www.wired.com/story/meta-removes-face-recognition-code-meta-ai-app-smart-glasses/',
                        'year' => 2026,
                        'author' => 'WIRED',
                    ],
                    [
                        'label' => 'Dossier judiciaire Alvarez c. Meta Platforms, 1:26-cv-10773 (district Nord de l\'Illinois), sur CourtListener',
                        'url' => 'https://www.courtlistener.com/docket/74753032/alvarez-v-meta-platforms-inc/',
                        'year' => 2026,
                        'author' => 'CourtListener (Free Law Project)',
                    ],
                ],
                'aliases' => [
                    'NameTag',
                ],
                'broader_slugs' => [
                    'reconnaissance-faciale',
                    'biometrie',
                ],
                'narrower_slugs' => [],
                'difficulty' => 'beginner',
                'icon' => '🕶️',
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
            $term->match_strategy = $t['match_strategy'];
            $term->dictionary_category_id = $this->resolveCategoryId($t['cat_slug']) ?? $fallbackCatId;
            $term->hero_image = ! empty($t['has_image']) ? 'images/glossaire/'.$t['slug'].'.webp' : null;
            $term->is_published = true;
            $term->sort_order = 985 + $i;
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
