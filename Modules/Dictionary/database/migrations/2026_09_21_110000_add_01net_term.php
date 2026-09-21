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
 * Ajout du terme « 01net » (2026-09-21), fiche redigee et verifiee en amont de cette migration ;
 * cette migration se limite a transposer le contenu deja controle dans la structure du glossaire,
 * sans reformulation.
 *
 * CONTROLE ANTI-DOUBLON EXECUTE localement le 2026-09-21, par grep sur les migrations existantes du
 * module (aucun acces au sitemap de production depuis cette tache, volontairement restreinte a
 * l ecriture des migrations) : aucun slug `01net` ni alias `01net.com` n existe dans
 * Modules/Dictionary/database/migrations/. Fiche NOUVELLE.
 *
 * ANGLE DE LA FICHE : situer l editeur actuel de 01net.com (KELEOPS FRANCE SAS, filiale du groupe
 * suisse KELEOPS AG) contre une attribution ancienne encore repandue (rattachement a un groupe
 * audiovisuel francais via NextRadioTV puis Altice Media), en distinguant aussi le site 01net.com
 * (lance en 2000) de l hebdomadaire professionnel 01 Informatique (1966, devenu IT for Business).
 * Methode : mentions legales datees comparees a l historique de cession disponible.
 *
 * AUCUN PARENT HIERARCHIQUE DECLARE : broader_slugs et narrower_slugs sont laisses vides, aucune
 * relation n a ete fournie avec le contenu source.
 *
 * has_image = true : l image existe deja (public/images/glossaire/01net.webp et .jpg, 1200x669),
 * verifiee avant l ecriture de cette migration.
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
                'name' => '01net',
                'slug' => '01net',
                'cat_slug' => 'intelligence-artificielle',
                'definition' => '01net.com est un média numérique grand public lancé en 2000. Selon ses mentions légales consultées le 21 septembre 2026, son éditeur est KELEOPS FRANCE SAS, une société établie à Paris et filiale de KELEOPS AG, dont le siège est à Zoug, en Suisse.

Le nom « 01 » remonte à l’hebdomadaire professionnel 01 Informatique, lancé en 1966 et devenu IT for Business, distinct du site grand public.

Son parcours explique pourquoi une attribution ancienne peut induire en erreur. En 2007, NextRadioTV rachète le Groupe Tests, dont le portefeuille comprend 01net.com. En 2013, le groupe cède les magazines papier 01net et 01 Business, mais conserve le site. Altice Media hérite ensuite de 01net.com via NextRadioTV. Le 15 juin 2023, des discussions avancées concernant sa cession à Keleops sont annoncées.

Connaître l’éditeur aide à situer ce qu’on lit, sans constituer un reproche au média. La méthode consiste à comparer les mentions légales datées avec l’historique disponible.',
                'analogy' => 'C’est comme vérifier le nom sur le bail d’un commerce : l’enseigne peut rester en place longtemps après le départ du locataire précédent.',
                'example' => 'Une recherche attribue encore 01net à un groupe audiovisuel français. Les mentions légales, elles, désignent KELEOPS FRANCE SAS : c’est la source qui tranche, parce qu’elle est une obligation juridique.',
                'did_you_know' => 'En 2013, les magazines papier 01net et 01 Business ont été cédés alors que le site 01net.com restait dans le groupe : le sort du site s’est séparé de celui des publications imprimées.',
                'one_sentence_answer' => '01net.com est un média tech français grand public lancé en 2000, édité par KELEOPS FRANCE SAS, filiale parisienne du groupe suisse KELEOPS AG selon ses mentions légales.',
                'faq' =>                 [
                    [
                        'question' => 'Une recherche attribue encore 01net à un groupe audiovisuel français. Comment vérifier?',
                        'answer' => 'Commencez par les mentions légales du site, et notez la date de consultation. Celles consultées le 21 septembre 2026 désignent KELEOPS FRANCE SAS, filiale de KELEOPS AG, établie en Suisse. L’attribution à un groupe audiovisuel correspond à une étape antérieure : 01net.com est passé par NextRadioTV, puis par Altice Media. Les mentions légales font autorité parce qu’elles répondent à une obligation légale, alors qu’une page « à propos » relève de la communication.',
                    ],
                    [
                        'question' => 'Est-ce que 01 Informatique et 01net désignent la même publication?',
                        'answer' => 'Non, et la distinction évite une confusion courante. Lancé en 1966, 01 Informatique était un hebdomadaire destiné aux professionnels du secteur informatique. Ce titre est devenu IT for Business, distinct de 01net.com. Le site 01net.com a été lancé en 2000 comme marque numérique grand public. Autre distinction utile : les magazines papier 01net et 01 Business ont été cédés en 2013, alors que le site est resté dans le groupe.',
                    ],
                ],
                'sources' =>                 [
                    [
                        'label' => 'Mentions légales de 01net.com, qui identifient l’éditeur, sa société mère et le directeur de la publication',
                        'url' => 'https://www.01net.com/info/mentions-legales/',
                        'year' => 2026,
                        'author' => 'KELEOPS FRANCE SAS',
                    ],
                    [
                        'label' => 'Document de référence 2007 de NextRadioTV, qui documente le rachat du Groupe Tests et son périmètre',
                        'url' => 'https://alticefrance.com/sites/default/files/pdf/DDR%202007%20FR.pdf',
                        'year' => 2007,
                        'author' => 'NextRadioTV',
                    ],
                ],
                'aliases' =>                 [
                    '01net.com',
                ],
                'broader_slugs' => [],
                'narrower_slugs' => [],
                'difficulty' => 'beginner',
                'icon' => '📰',
                'type' => 'explainer',
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
            $term->sort_order = 1010 + $i;
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
