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
 * Ajout du terme « Muse Image » (2026-09-13), demande du fondateur : « modeles d images Emu et
 * Muse Image ».
 *
 * UNE SEULE FICHE POUR LES DEUX, et le motif est une MESURE, pas une preference. La demande
 * nommait deux modeles ; le glossaire n en recoit qu un, qui raconte la lignee.
 *   « Muse Image » ...... 5 actualites publiees sur le site
 *   « Emu » (le modele) .. 0 actualite
 * Les 18 correspondances du motif « Emu » dans la veille sont TOUTES des faux positifs : Temu la
 * place de marche, « emulation », « consommateurs ». Aucune ne designe le modele de Meta. Une
 * fiche « Emu » serait donc une page que personne n atteint, et le site n en parle jamais. Emu est
 * a la place explique DANS cette fiche, en definition et dans une FAQ dediee, comme le
 * predecesseur qu il est.
 *
 * ANTI-DOUBLON par famille de motifs contre la base de PRODUCTION
 * (emu|muse|imagen|dall|midjourney|stable diffusion|flux|firefly|text-to-image|diffusion) :
 * 7 fiches voisines existent (dall-e, midjourney, stable-diffusion, text-to-image,
 * modele-de-diffusion, modele-diffusion-detail, adobe). AUCUNE ne couvre Muse Image ni Emu.
 * Rattachee par broader_slugs a text-to-image et modele-de-diffusion.
 *
 * ALIAS - LE RISQUE LE PLUS ELEVE RENCONTRE JUSQU ICI, et il est ecarte par construction :
 *   « Emu » est une sous-chaine de « Temu », mot tres frequent dans l actualite technologique -
 *     l alias poserait un lien sur chaque article parlant de la place de marche. MESURE : 18
 *     correspondances, 18 faux positifs. ECARTE.
 *   « Muse » seul est un nom commun francais, un groupe de musique celebre, et le nom d au moins
 *     deux autres modeles d IA (Google et Microsoft). ECARTE.
 *   Seul « Muse Image », forme composee, est retenu. match_strategy = case_sensitive.
 * Rappel du piege : case_sensitive protege du nom commun en minuscules, PAS d un autre nom propre
 * capitalise - « Muse » le groupe s ecrit avec une majuscule.
 *
 * SUJET SENSIBLE - la fiche touche a la generation d images de personnes reelles sans leur
 * consentement. Elle reste factuelle, attribue chaque affirmation de capacite a Meta (« selon
 * Meta », « Meta affirme »), ne prend pas parti et ne donne AUCUNE indication operationnelle.
 * LA DISTINCTION A NE PAS DEFORMER, et c est le coeur de la FAQ 2 : c est une FONCTION qui a ete
 * retiree vers le 10 juillet 2026 - celle qui permettait de generer des images de personnes en
 * mentionnant leur compte Instagram public, activee par defaut - et NON le modele Muse Image, qui
 * est reste disponible. Le titre de l article 27578 du site, « Meta kills Muse Image feature »,
 * dit bien « feature ». Ma premiere lecture avait conclu a tort que le modele entier avait ete
 * supprime.
 * Ne pas confondre non plus avec l action collective du 4 septembre 2026 (article 48782) : elle
 * vise NameTag, une autre fonction, et n est pas citee dans cette fiche.
 *
 * SOURCES verifiees par requete reelle avec controle du TITRE servi :
 *   about.fb.com/news/2026/07/introducing-muse-image-meta-ai/ -> 200,
 *     « Introducing Muse Image: Image Generation Built for Your World »
 *   about.fb.com/fr/news/2023/09/meta-connect-2023-... -> 200, annonce de Meta Connect 2023 ou Emu
 *     est presente
 * A RETENIR : ai.meta.com repond 400 a tout recuperateur automatique, y compris sur la racine du
 * blogue. Les adresses ai.meta.com citees par les moteurs de recherche ne sont donc pas
 * verifiables et n ont PAS ete retenues comme sources, malgre leur pertinence apparente.
 *
 * DEUX AJOUTS NON SOURCES, FABRIQUES PAR LA REDACTION DELEGUEE ET RETIRES : « pour un site web »
 * accole a la demonstration du code QR, et « suite a des preoccupations » accole au retrait de la
 * fonction - cette derniere formulation pretait une intention a Meta que la source n exprime pas.
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
                'name' => 'Muse Image',
                'slug' => 'muse-image',
                'cat_slug' => 'intelligence-artificielle',
                'definition' => 'Muse Image est le premier modèle de génération de médias de Meta Superintelligence Labs, annoncé le 7 juillet 2026. Meta le décrit comme « agentique » : plutôt que de transformer directement une consigne en image, il raisonnerait sur la tâche, choisirait des outils adaptés, puis réviserait sa propre production avant de la rendre. Parmi les capacités que l\'entreprise revendique : composer une image à partir de plusieurs références, modifier seulement l\'élément visé sans refaire le reste, poursuivre une création sur plusieurs échanges en gardant le contexte, annoter directement l\'image pour désigner une zone, produire du texte lisible, et générer des codes QR réellement fonctionnels. Muse Image succède à Emu, acronyme d\'Expressive Media Universe, le premier modèle de fondation de Meta pour l\'image à partir de texte, annoncé le 27 septembre 2023 et qui alimentait Meta AI, les autocollants générés par IA ainsi que les fonctions Restyle et Backdrop d\'Instagram.',
                'analogy' => 'Comme un assistant qui relit son travail et le corrige avant de le remettre, là où un simple outil se contente d\'exécuter la commande.',
                'example' => 'Le 7 juillet 2026, Meta a présenté le modèle générant un code QR réellement scannable, ce qui suppose d\'exécuter du code plutôt que de dessiner l\'apparence d\'un code.',
                'did_you_know' => 'Produire un code QR qui fonctionne vraiment n\'est pas un exercice de dessin : il faut exécuter du code. C\'est ce qui distingue un modèle outillé d\'un simple générateur d\'images.',
                'one_sentence_answer' => 'Muse Image est le modèle de génération et d\'édition d\'images annoncé par Meta le 7 juillet 2026, présenté comme agentique parce qu\'il utiliserait des outils et réviserait son propre résultat.',
                'faq' => [
                    [
                        'question' => 'Qu\'était Emu, et qu\'est-ce qui a changé depuis?',
                        'answer' => 'Emu, annoncé en septembre 2023, était le premier modèle de fondation de Meta pour produire une image à partir de texte. Il alimentait Meta AI, les autocollants générés par IA et deux fonctions d\'Instagram, Restyle et Backdrop. Deux travaux dérivés ont suivi en novembre 2023, présentés comme de la recherche : Emu Edit et Emu Video. Muse Image vient d\'une autre équipe et d\'une autre approche, dite agentique.',
                    ],
                    [
                        'question' => 'Une fonction a été retirée peu après le lancement : laquelle, et le modèle existe-t-il encore?',
                        'answer' => 'La distinction compte. Une fonction associée permettait de générer des images de personnes en mentionnant simplement leur compte Instagram public, et elle était activée par défaut, la désactivation devant se faire à la main dans les paramètres. Meta l\'a retirée vers le 10 juillet 2026. C\'est cette FONCTION qui a disparu, pas le modèle : Muse Image est resté disponible pour la génération et l\'édition d\'images.',
                    ],
                    [
                        'question' => 'Que veut dire « agentique » pour un modèle d\'images?',
                        'answer' => 'Un générateur classique suit un chemin unique, de la consigne vers l\'image. Meta affirme que Muse Image fait autre chose : il évalue la tâche, peut consulter le web pour récupérer du contexte, se servir d\'outils de code, repérer une erreur dans son propre résultat et la corriger, et consacrer davantage de calcul quand la demande est difficile. Ce sont des affirmations de l\'entreprise, pas une mesure indépendante.',
                    ],
                ],
                'sources' => [
                    [
                        'label' => 'Meta, « Introducing Muse Image: Image Generation Built for Your World »',
                        'url' => 'https://about.fb.com/news/2026/07/introducing-muse-image-meta-ai/',
                        'year' => 2026,
                        'author' => 'Meta Newsroom',
                    ],
                    [
                        'label' => 'Meta, annonce de Meta Connect 2023 présentant Emu (Expressive Media Universe)',
                        'url' => 'https://about.fb.com/fr/news/2023/09/meta-connect-2023-quest-3-innovations-ia-lunettes-connectees-nouvelle-generation-et-les-prochaines-etapes-du-metavers/',
                        'year' => 2023,
                        'author' => 'Meta Newsroom',
                    ],
                ],
                'aliases' => [
                    'Muse Image',
                ],
                'broader_slugs' => [
                    'text-to-image',
                    'modele-de-diffusion',
                ],
                'narrower_slugs' => [],
                'difficulty' => 'intermediate',
                'icon' => '🎨',
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
            $term->sort_order = 984 + $i;
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
