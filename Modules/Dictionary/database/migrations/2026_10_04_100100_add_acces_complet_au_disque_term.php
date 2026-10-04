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
 * Ajout du terme « Accès complet au disque » (Full Disk Access, macOS). La fiche « tcc » le
 * cite déjà à plusieurs reprises sans lui consacrer de page; l'annonce d'Apple du 2 octobre 2026
 * sur de nouveaux contrôles face aux agents d'IA en fait un sujet à part entière.
 *
 * CONTROLE ANTI-DOUBLON (2026-10-04) : relevé du plan de site de production, motif
 * disque|disk|full|fda|permission. Aucune fiche portant la notion; elle n'était atteinte que
 * par mention dans « tcc ». Notion voisine et distincte (cas particulier le plus large de TCC) :
 * fiche nouvelle, reliée par broader_slugs=['tcc', 'macos'], et ajout du slug aux
 * narrower_slugs de « tcc » (mesuré puis enrichi, jamais écrasé).
 *
 * ALIAS : « Full Disk Access » et « accès complet au disque ». AUCUN alias « FDA » : sigle de
 * l'agence américaine du médicament (collision de nom propre).
 *
 * FORMULATION : le texte dit « contrôles », jamais « limiter ». TechCrunch a corrigé son article;
 * il s'agit d'un consentement éclairé, et Apple n'a donné aucun détail technique.
 *
 * Typographie québécoise (OQLF) : espace insécable U+00A0 devant chaque deux-points, aucune
 * espace avant ; ! ?, guillemets droits, aucun tiret cadratin.
 *
 * Migration idempotente. down() retire ce terme et le slug ajouté à « tcc » (seul ce slug,
 * jamais un remplacement aveugle du tableau).
 */
return new class extends Migration
{
    private const NEW_SLUG = 'acces-complet-au-disque';

    private const PARENT_SLUG = 'tcc';

    private function resolveCategoryId(string $slug): ?int
    {
        return Category::where('slug->fr_CA', $slug)->value('id')
            ?? Category::where('slug->fr', $slug)->value('id');
    }

    private function term(): array
    {
        return [
            'name' => 'Accès complet au disque',
            'slug' => self::NEW_SLUG,
            'cat_slug' => 'securite-et-ethique',
            'definition' => "L'accès complet au disque (Full Disk Access en anglais) est une autorisation de macOS qui permet à une application de lire à peu près tout le contenu du Mac, y compris des données que le système protège normalement : courriels, messages, historique de navigation et fichiers personnels. Elle se règle dans Réglages Système, sous Confidentialité et sécurité. Elle fait partie du système TCC (Transparency, Consent, and Control), mais elle est la plus large : au lieu d'autoriser une ressource à la fois (caméra, contacts, photos), elle contourne en bloc ces contrôles. Apple l'a conçue pour que les applications de sauvegarde puissent fonctionner. Elle intéresse maintenant les agents d'IA de bureau, qui demandent cet accès pour lire les données de l'utilisateur. Le 2 octobre 2026, Apple a indiqué qu'elle ajouterait des contrôles pour que cet accès ne s'accorde que par une action explicite de l'utilisateur, sans en préciser encore les détails.",
            'analogy' => "C'est le passe-partout de l'immeuble : une seule clé ouvre toutes les portes, pas seulement celle de la buanderie.",
            'example' => "Une application de sauvegarde a besoin de copier tout le Mac, donc elle demande l'accès complet au disque. Un agent d'IA qui demande la même autorisation pourrait aussi lire tes courriels et tes messages. Il faut donc peser la demande.",
            'did_you_know' => "Apple précise que l'accès complet au disque contourne en grande partie les contrôles de vie privée du système, et qu'il a été prévu au départ pour les logiciels de sauvegarde, pas pour les assistants d'IA.",
            'one_sentence_answer' => "L'accès complet au disque est la permission macOS la plus large : elle donne à une application la lecture de presque toutes les données du Mac, y compris courriels, messages et historique de navigation, en contournant les contrôles habituels de vie privée.",
            'faq' => [
                [
                    'question' => 'Comment savoir quelles applications ont cet accès sur mon Mac?',
                    'answer' => "Ouvre Réglages Système, puis Confidentialité et sécurité, puis Accès complet au disque. La liste montre chaque application et son interrupteur. Désactive ce que tu ne reconnais pas ou n'utilises plus.",
                ],
                [
                    'question' => 'Faut-il l\'accorder à un agent d\'IA?',
                    'answer' => "Seulement si tu comprends à quoi l'application s'en servira et que tu lui fais confiance. Apple avertit que les risques grandissent à mesure que les agents deviennent autonomes. Si l'outil fonctionne sans cet accès, laisse-le désactivé.",
                ],
                [
                    'question' => 'Quelle est la différence avec les autres permissions macOS?',
                    'answer' => "Les autres (caméra, micro, contacts) ouvrent une ressource précise. L'accès complet au disque ouvre presque tout d'un coup, ce qui en fait la permission à accorder avec le plus de prudence.",
                ],
            ],
            'sources' => [
                [
                    'label' => 'Documentation Apple : autoriser des applications à accéder à des fichiers sur Mac, section Accès complet au disque',
                    'url' => 'https://support.apple.com/guide/mac-help/allow-apps-access-files-mac-mchlccb25729/mac',
                    'year' => 2026,
                    'author' => 'Apple',
                ],
                [
                    'label' => "Apple annonce de nouveaux contrôles pour l'accès complet au disque en raison des risques liés aux agents d'IA",
                    'url' => 'https://techcrunch.com/2026/10/02/apple-says-its-tightening-macos-full-disk-access-controls-due-to-new-risks-from-ai-agents/',
                    'year' => 2026,
                    'author' => 'Sarah Perez (TechCrunch)',
                ],
                [
                    'label' => "Apple dit resserrer les contrôles de confidentialité de macOS face aux agents d'IA",
                    'url' => 'https://9to5mac.com/2026/10/02/apple-says-its-tightening-macos-privacy-controls-amid-the-rise-of-ai-agents/',
                    'year' => 2026,
                    'author' => 'Chance Miller (9to5Mac)',
                ],
            ],
            'aliases' => ['Full Disk Access', 'accès complet au disque'],
            'broader_slugs' => [self::PARENT_SLUG, 'macos'],
            'narrower_slugs' => [],
            'difficulty' => 'intermediate',
            'icon' => '🗝️',
            'type' => 'explainer',
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
        } else {
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
            $term->sort_order = 1014;
            $term->save();

            echo "[glossaire] terme ajoute : {$t['slug']}\n";
        }

        // Graphe : ajoute ce terme aux enfants de « tcc » SANS écraser les enfants présents.
        $parent = Term::where('slug->fr_CA', self::PARENT_SLUG)->first();
        if ($parent) {
            $narrower = is_array($parent->narrower_slugs) ? $parent->narrower_slugs : [];
            if (! in_array(self::NEW_SLUG, $narrower, true)) {
                $narrower[] = self::NEW_SLUG;
                $parent->narrower_slugs = array_values($narrower);
                $parent->save();
                echo '[glossaire] narrower_slugs enrichi sur '.self::PARENT_SLUG.' : + '.self::NEW_SLUG."\n";
            }
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $parent = Term::where('slug->fr_CA', self::PARENT_SLUG)->first();
        if ($parent) {
            $narrower = is_array($parent->narrower_slugs) ? $parent->narrower_slugs : [];
            if (in_array(self::NEW_SLUG, $narrower, true)) {
                $parent->narrower_slugs = array_values(array_filter(
                    $narrower,
                    static fn ($v) => $v !== self::NEW_SLUG
                ));
                $parent->save();
                echo '[glossaire] narrower_slugs restaure sur '.self::PARENT_SLUG.' : - '.self::NEW_SLUG."\n";
            }
        }

        Term::where('slug->fr_CA', self::NEW_SLUG)->delete();
        echo '[glossaire] terme retire : '.self::NEW_SLUG."\n";
    }
};
