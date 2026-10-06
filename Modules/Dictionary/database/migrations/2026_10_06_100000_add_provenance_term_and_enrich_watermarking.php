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
 * Deux changements de glossaire, issus du cycle de veille 2026-10-05 (fiche 66190 : filigrane de
 * texte par defaut de ChatGPT pour l'UE).
 *
 * 1) ENRICHISSEMENT de la fiche existante « watermarking-ia » (id 221) : ajout des alias
 *    « filigrane de texte », « text watermarking » et « SynthID Text ». Le controle anti-doublon
 *    (2026-10-06) a montre que sa definition couvre DEJA « texte, image, audio, video » : le
 *    filigrane de texte en est un cas particulier, pas une notion distincte. On enrichit donc
 *    plutot que de creer un doublon. Le mot nu « filigrane » n'est PAS ajoute (trop generique :
 *    il mordrait sur les filigranes d'images, de PDF, de CSS).
 *
 * 2) CREATION de la fiche « provenance de contenu » (C2PA / Content Credentials). Controle
 *    anti-doublon : aucune fiche ne porte C2PA, « content credentials » ni « provenance » dans son
 *    nom ou ses alias (verifie en prod sur name ET aliases). Notion VOISINE mais DISTINCTE du
 *    watermarking : la provenance attache des metadonnees SIGNEES a cote du fichier, le filigrane
 *    cache un signal DANS le contenu. Aucune parente hierarchique fabriquee : les deux se relient
 *    par l'auto-lien, la fiche nommant « filigrane » et « watermarking » dans sa definition.
 *
 * Sources croisees : spec C2PA (c2pa.org) + Content Authenticity Initiative (contentauthenticity.org),
 * recoupees par une recherche (openrouter sonar-pro, Perplexity etant bloque par Cloudflare ce jour-la,
 * signale). Faits confirmes : C2PA fonde en fevrier 2021, projet de la Joint Development Foundation
 * (groupe Linux Foundation), fondateurs Adobe/Microsoft/Arm/BBC/Intel/Truepic, spec 2.2 (2025).
 *
 * Typographie OQLF : espace insecable U+00A0 devant chaque deux-points, aucune espace avant;!?,
 * guillemets droits, aucun tiret cadratin. (Les accents du contenu FR sont dans les chaines ci-dessous.)
 *
 * Migration idempotente. down() retire le terme cree ET restaure les 4 alias d'origine de
 * watermarking-ia.
 */
return new class extends Migration
{
    private const NEW_SLUG = 'provenance-de-contenu';

    private const WM_SLUG = 'watermarking-ia';

    /** Les 4 alias d'origine de watermarking-ia, pour un down() exact. */
    private const WM_ALIASES_ORIG = [
        'Watermarking IA / SynthID (marquage de contenu généré)',
        'digital watermarking',
        'tatouage numérique',
        'AI-generated content labeling',
    ];

    private const WM_ALIASES_ENRICHED = [
        'Watermarking IA / SynthID (marquage de contenu généré)',
        'digital watermarking',
        'tatouage numérique',
        'AI-generated content labeling',
        'filigrane de texte',
        'text watermarking',
        'SynthID Text',
    ];

    private function resolveCategoryId(string $slug):?int
    {
        return Category::where('slug->fr_CA', $slug)->value('id')
           ?? Category::where('slug->fr', $slug)->value('id');
    }

    private function term(): array
    {
        return [
            'name' => 'Provenance de contenu',
            'slug' => self::NEW_SLUG,
            'cat_slug' => 'securite-et-ethique',
            'acronym_full' => null,
            'definition' => "La provenance de contenu regroupe les informations vérifiables sur l'origine d'un contenu numérique : qui l'a créé, avec quel appareil ou logiciel, quand, et quelles modifications il a subies ensuite. Le standard ouvert de référence est le C2PA (Coalition for Content Provenance and Authenticity), fondé en février 2021 et développé sous la Joint Development Foundation (groupe Linux Foundation) par Adobe, Microsoft, Arm, BBC, Intel et Truepic, rejoints depuis par Google, OpenAI, Sony et des fabricants d'appareils photo comme Leica, Nikon et Canon. Ces informations, présentées sous le nom de Content Credentials, sont inscrites dans un manifeste signé cryptographiquement : toute altération devient détectable et le certificat identifie l'entité signataire. Contrairement au filigrane, qui cache un signal dans le contenu lui-même, la provenance attache des métadonnées à côté du fichier. Sa faiblesse est connue : une capture d'écran, un recadrage ou un réexport peuvent effacer entièrement ces métadonnées, ce qui n'altère pas le contenu mais lui retire son historique.",
            'analogy' => "C'est le certificat d'authenticité d'une oeuvre d'art : il voyage avec elle et dit qui l'a faite, puis qui l'a retouchée.",
            'example' => "Depuis 2024, les images produites par ChatGPT et par les outils de Google portent des Content Credentials C2PA. Une photo prise avec un Leica récent reçoit, dès le déclenchement, un manifeste signé indiquant l'appareil et l'auteur.",
            'did_you_know' => "Le standard C2PA (version 2.2, 2025) qualifie le filigrane et l'empreinte perceptuelle de « soft bindings » : des rattrapages qui relient un fichier à son manifeste quand les métadonnées ont été effacées.",
            'one_sentence_answer' => "La provenance de contenu désigne des métadonnées signées cryptographiquement qui attestent l'origine et l'historique de modification d'un fichier numérique, selon le standard ouvert C2PA affiché sous le nom de Content Credentials.",
            'faq' => [
                [
                    'question' => 'Quelle différence avec le filigrane (watermarking)?',
                    'answer' => "Le filigrane cache un signal dans le contenu lui-même; la provenance attache des métadonnées signées à côté du fichier. Le filigrane survit mieux à une capture d'écran, la provenance porte un historique détaillé et vérifiable. Les deux sont complémentaires, pas interchangeables, et le C2PA combine souvent les deux.",
                ],
                [
                    'question' => 'Ces métadonnées peuvent-elles être retirées?',
                    'answer' => "Oui. Une capture d'écran, un recadrage ou un réexport avec un outil qui ne les conserve pas efface entièrement les Content Credentials. La signature protège contre la falsification, pas contre la suppression. Les versions dites durables ajoutent un filigrane et une empreinte perceptuelle pour retrouver l'information perdue.",
                ],
                [
                    'question' => 'Est-ce utile pour une organisation québécoise?',
                    'answer' => "Oui, comme signal de transparence : prouver qu'une photo ou un document vient bien de soi, ou reconnaître un contenu généré par IA. La loi européenne sur l'IA pousse vers l'étiquetage du contenu synthétique, et la provenance est l'une des voies pour y répondre.",
                ],
            ],
            'sources' => [
                [
                    'label' => 'C2PA - Coalition for Content Provenance and Authenticity (spécification ouverte)',
                    'url' => 'https://c2pa.org/',
                    'year' => 2021,
                    'author' => 'Joint Development Foundation (Linux Foundation)',
                ],
                [
                    'label' => 'Content Authenticity Initiative - Content Credentials',
                    'url' => 'https://contentauthenticity.org/',
                    'year' => 2024,
                    'author' => 'Content Authenticity Initiative (Adobe et partenaires)',
                ],
            ],
            'aliases' => ['content provenance', 'C2PA', 'Content Credentials', "informations d'identification de contenu", 'provenance du contenu'],
            'broader_slugs' => [],
            'narrower_slugs' => [],
            'difficulty' => 'intermediate',
            'icon' => '🔏',
            'type' => 'explainer',
            'match_strategy' => 'loose',
        ];
    }

    public function up(): void
    {
        if (DB::connection()->getDriverName()!== 'mysql') {
            return;
        }

        if (! class_exists(Term::class) ||! class_exists(Category::class)) {
            echo "[glossaire] modele Term/Category absent, ignore\n";

            return;
        }

        // 1) Enrichir watermarking-ia : ajouter les alias du filigrane de texte (idempotent).
        $wm = Term::where('slug->fr_CA', self::WM_SLUG)->first()
           ?? Term::where('slug->fr', self::WM_SLUG)->first();
        if ($wm!== null) {
            $wm->aliases = self::WM_ALIASES_ENRICHED;
            $wm->save();
            echo "[glossaire] alias enrichis : ".self::WM_SLUG."\n";
        } else {
            echo "[glossaire] watermarking-ia absent, enrichissement ignore\n";
        }

        // 2) Creer la fiche provenance de contenu (idempotent).
        $t = $this->term();
        if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
            echo "[glossaire] slug deja present, skip : {$t['slug']}\n";

            return;
        }

        $term = new Term();
        foreach (['name', 'slug', 'definition', 'analogy', 'example', 'did_you_know', 'one_sentence_answer'] as $tf) {
            $term->setTranslations($tf, ['fr_CA' => $t[$tf], 'fr' => $t[$tf]]);
        }
        $term->acronym_full = $t['acronym_full'];
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
        $term->sort_order = 1016;
        $term->save();

        echo "[glossaire] terme ajoute : {$t['slug']}\n";
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        Term::where('slug->fr_CA', self::NEW_SLUG)->delete();

        $wm = Term::where('slug->fr_CA', self::WM_SLUG)->first()
           ?? Term::where('slug->fr', self::WM_SLUG)->first();
        if ($wm!== null) {
            $wm->aliases = self::WM_ALIASES_ORIG;
            $wm->save();
        }

        echo "[glossaire] terme retire et alias watermarking-ia restaures : ".self::NEW_SLUG."\n";
    }
};
