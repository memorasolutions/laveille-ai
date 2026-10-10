<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * SOURCE UNIQUE de la navigation de l'entête (ticket #3013).
 *
 * Une seule définition de l'arbre de navigation, dont dérivent :
 *   - l'entête de laveille.ai (méga-menus desktop, repli mobile, barre latérale du hamburger),
 *     via les gabarits partials/nav/* ;
 *   - l'API publique GET /api/header-nav (méthode toApi()), consommée par l'académie Moodle.
 *
 * Ajouter, retirer ou renommer une entrée = modifier CE fichier, et elle change des deux côtés.
 *
 * Réutilisable dans un autre projet Laravel : aucune dépendance au reste du thème. Il suffit de
 * copier ce fichier, d'adapter les routes nommées et les libellés de buildTree(), puis de brancher
 * les gabarits ou la route API. Chaque entrée reste gardée par Route::has() et par ses drapeaux de
 * configuration : un module désactivé retire simplement son lien, sans rien casser.
 *
 * Forme interne d'une entrée (les clés de présentation `icon`, `mobile` et `sidebar` servent
 * uniquement aux gabarits et ne sortent JAMAIS dans l'API) :
 *   label, url, subtitle?  - contrat public
 *   icon                   - classe Themify de l'icône du panneau desktop
 *   mobile / sidebar       - [emoji, libellé, rang, gras?] pour le repli mobile et la barre latérale ;
 *                            absent = l'entrée n'y figure pas
 */

namespace Modules\FrontTheme\Services;

use Illuminate\Support\Facades\Route;

class HeaderNavService
{
    /**
     * Fiches stars de l'annuaire (GA4 30 j : Poe, ChatGPT, Canva AI, Wooclap, Claude Design).
     *
     * @var list<array{slug:string,name:string,desc:string}>
     */
    private const DIRECTORY_STARS = [
        ['slug' => 'poe', 'name' => 'Poe', 'desc' => 'Multi-IA en un'],
        ['slug' => 'chatgpt', 'name' => 'ChatGPT', 'desc' => "L'IA générale OpenAI"],
        ['slug' => 'canva-ai', 'name' => 'Canva AI', 'desc' => 'Création visuelle'],
        ['slug' => 'wooclap', 'name' => 'Wooclap', 'desc' => 'Quiz interactifs'],
        ['slug' => 'claude-design', 'name' => 'Claude Design', 'desc' => 'Anthropic premium'],
    ];

    /**
     * Arbre complet (contrat public + clés de présentation).
     *
     * @return list<array<string,mixed>>
     */
    public function tree(): array
    {
        $directoryCount = $this->count('directory_tools_count', fn () => class_exists(\Modules\Directory\Models\Tool::class)
            ? \Modules\Directory\Models\Tool::where('status', 'published')->count() : 0);
        $dictionaryCount = $this->count('dictionary_terms_count', fn () => class_exists(\Modules\Dictionary\Models\Term::class)
            ? \Modules\Dictionary\Models\Term::where('is_published', 1)->count() : 0);
        $acronymsCount = $this->count('acronyms_count', fn () => class_exists(\Modules\Acronyms\Models\Acronym::class)
            ? \Modules\Acronyms\Models\Acronym::count() : 0);

        $nodes = [
            [
                'id' => 'home',
                'label' => __('Accueil'),
                'url' => route('home'),
                'sidebar' => ['title' => __('Accueil'), 'emoji' => '🏠', 'label' => __("Page d'accueil")],
            ],
            $this->toolsPanel(),
        ];

        if (Route::has('directory.index')) {
            $nodes[] = $this->directoryPanel($directoryCount);
        }

        $nodes[] = $this->learnPanel($dictionaryCount, $acronymsCount);

        if (Route::has('academy.index') && ! config('academy.under_construction')) {
            $nodes[] = ['id' => 'academie', 'label' => __('Académie'), 'url' => route('academy.index')];
        }

        if (Route::has('books.index') && ! config('books.under_construction')) {
            $nodes[] = [
                'id' => 'livres',
                'label' => __('Livres'),
                'url' => route('books.index'),
                'sidebar' => ['title' => __('Livres'), 'emoji' => '', 'label' => __('La bibliothèque')],
            ];
        }

        return $nodes;
    }

    /**
     * Charge utile publique de GET /api/header-nav (contrat figé, sans clés de présentation).
     *
     * @return array{version:string,generated_at:string,menu:list<array<string,mixed>>,footer:array{legal:list<array{label:string,url:string}>}}
     */
    public function toApi(): array
    {
        $menu = array_map(fn (array $node) => $this->apiNode($node), $this->tree());
        $footer = ['legal' => $this->legalLinks()];

        return [
            'version' => substr(hash('sha256', (string) json_encode([$menu, $footer], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)), 0, 16),
            'generated_at' => now()->toIso8601String(),
            'menu' => $menu,
            'footer' => $footer,
        ];
    }

    /**
     * Entrées d'un panneau pour une surface de présentation ('mobile' ou 'sidebar'), à plat et
     * triées par rang. Les gabarits n'ont ainsi aucune logique d'ordre à dupliquer.
     *
     * @param  array<string,mixed>  $node
     * @return list<array{emoji:string,label:string,url:string,strong:bool}>
     */
    public static function surfaceItems(array $node, string $surface): array
    {
        $rows = [];
        foreach ($node['children'] ?? [] as $group) {
            foreach ($group['items'] as $item) {
                if (! isset($item[$surface])) {
                    continue;
                }
                [$emoji, $label, $rank] = $item[$surface];
                $rows[] = [
                    'rank' => $rank,
                    'emoji' => $emoji,
                    'label' => $label,
                    'url' => $item['url'],
                    'strong' => (bool) ($item[$surface][3] ?? false),
                ];
            }
        }
        usort($rows, fn (array $a, array $b) => $a['rank'] <=> $b['rank']);

        return array_map(fn (array $r) => [
            'emoji' => $r['emoji'], 'label' => $r['label'], 'url' => $r['url'], 'strong' => $r['strong'],
        ], $rows);
    }

    // -------------------------------------------------------------------------------------------
    // Panneaux
    // -------------------------------------------------------------------------------------------

    /** @return array<string,mixed> */
    private function toolsPanel(): array
    {
        $groups = [
            [
                'group' => __('Productivité'),
                'items' => array_values(array_filter([
                    $this->item(__('Brain Dump 2026'), url('/outils/brain-dump'), __('10 min papier + IA = clarté'), 'ti-light-bulb', ['🧠', __('Brain Dump 2026'), 1], ['🧠', __('Brain Dump 2026'), 1]),
                    $this->item(__('Constructeur de prompts'), url('/outils/constructeur-prompts'), __("Maîtrisez l'art du prompt IA"), 'ti-pencil', ['✏️', __('Constructeur de prompts'), 2], ['✏️', __('Constructeur de prompts'), 2]),
                    $this->item(__('Anonymiseur'), url('/outils/anonymiseur'), __('Masque tes infos avant de les confier à une IA'), 'ti-shield', ['🛡️', __('Anonymiseur'), 3], ['🛡️', __('Anonymiseur'), 3]),
                    Route::has('directory.compare-by-ids')
                        ? $this->item(__("Comparateur d'outils IA"), route('directory.compare-by-ids'), __("Jusqu'à 6 outils côte à côte"), 'ti-layout-column2', ['🆚', __("Comparateur d'outils IA"), 4], ['🆚', __("Comparateur d'outils IA"), 4], true)
                        : null,
                ])),
            ],
            [
                'group' => __('Création'),
                'items' => array_values(array_filter([
                    $this->item(__('Générateur de mots croisés'), url('/outils/mots-croises'), __('Grilles personnalisées + PDF'), 'ti-layout-grid3', ['🔤', __('Mots croisés'), 5], ['🔤', __('Générateur mots croisés'), 5]),
                    $this->item(__('Générateur de code QR'), url('/outils/code-qr'), __('QR personnalisable PNG/SVG'), 'ti-mobile', null, ['📱', __('Code QR'), 6]),
                    Route::has('shorturl.create')
                        ? $this->item(__('Raccourcir un lien'), route('shorturl.create'), __('veille.la gratuit + QR'), 'ti-link', null, ['🔗', __('Raccourcir un lien'), 10], true)
                        : null,
                ])),
            ],
            [
                'group' => __('Détente'),
                'items' => [
                    $this->item(__('Sudoku quotidien'), url('/outils/sudoku'), __('Nouvelle grille chaque jour'), 'ti-layout-grid3', ['🧩', __('Sudoku'), 6], ['🧩', __('Sudoku quotidien'), 7]),
                    $this->item(__("QT\u{00A0}: Quotient Techno"), url('/outils/qt'), __('Teste ton quotient techno'), 'ti-help', ['🧠', __("QT\u{00A0}: Quotient Techno"), 9], ['🧠', __("QT\u{00A0}: Quotient Techno"), 8]),
                    $this->item(__('Grilles partagées'), url('/jeumc'), __('Mots croisés à jouer en ligne'), 'ti-target', null, ['🎯', __('Grilles partagées'), 9], true),
                ],
            ],
            [
                'group' => __('Pratique'),
                'items' => array_values(array_filter([
                    $this->item(__('Calculatrice taxes QC'), url('/outils/calculatrice-taxes'), __('TPS et TVQ en un clic'), 'ti-money', ['💰', __('Calculatrice taxes QC'), 7], ['💰', __('Calculatrice taxes QC'), 11]),
                    $this->item(__('Simulateur fiscal Québec'), url('/outils/simulateur-fiscal'), __('Impôts et graphiques'), 'ti-bar-chart', ['📊', __('Simulateur fiscal Québec'), 8], ['📊', __('Simulateur fiscal Québec'), 12]),
                    Route::has('tools.quest.index') && config('tools.quest.enabled', false)
                        ? $this->item(__('Quête narrative IA'), route('tools.quest.index'), __("Les Sentiers de l'IA"), 'ti-game', ['🎮', __('Quête narrative'), 10], ['🎮', __('Quête narrative'), 13], true)
                        : null,
                ])),
            ],
        ];

        $node = [
            'id' => 'outils',
            'label' => __('Outils'),
            'url' => Route::has('tools.index') ? route('tools.index') : url('/outils'),
            'aria' => __('Menu Outils'),
            'layout' => ['columns' => 'repeat(2,1fr)', 'gap' => 24, 'padding' => 28],
            'children' => $groups,
        ];

        if (Route::has('tools.index')) {
            $node['cta'] = ['label' => __('Voir tous les outils gratuits'), 'url' => route('tools.index')];
            $node['mobile_cta'] = ['label' => '→ '.__('Tous les outils'), 'strong' => false];
            $node['sidebar_cta'] = ['label' => '→ '.__('Voir tous les outils'), 'strong' => true];
        }

        return $node;
    }

    /** @return array<string,mixed> */
    private function directoryPanel(int $count): array
    {
        $stars = [];
        foreach (self::DIRECTORY_STARS as $i => $star) {
            $stars[] = $this->item($star['name'], url('/annuaire/'.$star['slug']), $star['desc'], 'ti-panel', ['', $star['name'], 10 + $i], ['', $star['name'], 10 + $i]);
        }

        $navigation = [
            $this->item(
                __('Tous les outils'),
                route('directory.index'),
                $count.' '.__('avec avis + tutos'),
                'ti-search',
                ['🔍', __('Tous les outils (:count)', ['count' => $count]), 0],
                ['🔍', __('Tous les outils (:count)', ['count' => $count]), 0, true],
            ),
        ];
        if (Route::has('directory.leaderboard') && config('directory.leaderboard.enabled', false)) {
            $navigation[] = $this->item(__('Classement'), route('directory.leaderboard'), __('Top contributeurs'), 'ti-cup', ['🏆', __('Classement'), 100], ['🏆', __('Classement'), 100]);
        }
        if (Route::has('collections.index')) {
            $navigation[] = $this->item(__('Collections'), route('collections.index'), __('Listes communauté'), 'ti-folder', ['📁', __('Collections'), 101], ['📁', __('Collections'), 101], true);
        }

        return [
            'id' => 'annuaire',
            'label' => __('Annuaire'),
            'url' => route('directory.index'),
            'aria' => __('Menu Annuaire'),
            'layout' => ['columns' => '1.3fr 1fr', 'gap' => 20, 'padding' => 24],
            'children' => [
                // Les fiches stars gardent la marge basse de 2 px jusqu'à la dernière (rendu historique).
                ['group' => __('Top consultés'), 'items' => $stars, 'margin_last' => true],
                ['group' => __('Navigation'), 'items' => $navigation],
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function learnPanel(int $dictionaryCount, int $acronymsCount): array
    {
        $editorial = [];
        if (Route::has('news.index')) {
            $editorial[] = $this->item(__('Actualités'), route('news.index'), __('Veille IA et technologie'), 'ti-announcement', ['📰', __('Actualités'), 1], ['📰', __('Actualités'), 1]);
        }
        if (Route::has('blog.index')) {
            $editorial[] = $this->item(__('Blog'), route('blog.index'), __('Articles longs et guides'), 'ti-write', ['✍️', __('Blog'), 2], ['✍️', __('Blog'), 2]);
        }
        if (Route::has('faq.index')) {
            $editorial[] = $this->item(__('FAQ'), route('faq.index'), __('Questions fréquentes'), 'ti-help', ['❓', __('FAQ'), 5], ['❓', __('FAQ'), 5], true);
        }

        $reference = [];
        if (Route::has('dictionary.index')) {
            $reference[] = $this->item(__('Glossaire Techno'), route('dictionary.index'), $dictionaryCount.' '.__('termes et définitions'), 'ti-book', ['', __('Glossaire Techno'), 3], ['', __('Glossaire Techno (:count)', ['count' => $dictionaryCount]), 3]);
        }
        if (Route::has('acronyms.index')) {
            $reference[] = $this->item(__('Acronymes éducation'), route('acronyms.index'), $acronymsCount.' '.__('acronymes du Québec'), 'ti-text', ['🔤', __('Acronymes'), 4], ['🔤', __('Acronymes'), 4]);
        }
        if (Route::has('shop.index') && ! config('shop.maintenance', false)) {
            $reference[] = $this->item(__('Boutique'), route('shop.index'), __('Merch IA et technologie'), 'ti-shopping-cart', ['🛍️', __('Boutique'), 6], ['🛍️', __('Boutique'), 6], true);
        }

        return [
            'id' => 'apprendre',
            'label' => __('Apprendre'),
            'url' => Route::has('news.index') ? route('news.index') : url('/'),
            'aria' => __('Menu Apprendre'),
            'layout' => ['columns' => '1fr 1fr', 'gap' => 20, 'padding' => 24],
            'children' => [
                ['group' => __('Contenu éditorial'), 'items' => $editorial],
                ['group' => __('Référence'), 'items' => $reference],
            ],
        ];
    }

    // -------------------------------------------------------------------------------------------
    // Utilitaires
    // -------------------------------------------------------------------------------------------

    /**
     * @param  array{0:string,1:string,2:int,3?:bool}|null  $mobile
     * @param  array{0:string,1:string,2:int,3?:bool}|null  $sidebar
     * @param  bool  $tail  dernière entrée de son groupe dans la configuration complète : pas de marge basse,
     *                      même quand une entrée optionnelle qui la précède est absente (rendu historique)
     * @return array<string,mixed>
     */
    private function item(string $label, string $url, string $subtitle, string $icon, ?array $mobile, ?array $sidebar, bool $tail = false): array
    {
        $item = ['label' => $label, 'url' => $url, 'subtitle' => $subtitle, 'icon' => $icon];
        if ($tail) {
            $item['tail'] = true;
        }
        if ($mobile !== null) {
            $item['mobile'] = $mobile;
        }
        if ($sidebar !== null) {
            $item['sidebar'] = $sidebar;
        }

        return $item;
    }

    /** Compteur mis en cache une heure, comme avant l'extraction depuis l'entête. */
    private function count(string $key, \Closure $resolver): int
    {
        return (int) cache()->remember($key, 3600, $resolver);
    }

    /**
     * @param  array<string,mixed>  $node
     * @return array<string,mixed>
     */
    private function apiNode(array $node): array
    {
        $out = ['label' => $node['label'], 'url' => $node['url']];

        if (isset($node['children'])) {
            $out['children'] = array_map(fn (array $group) => [
                'group' => $group['group'],
                'items' => array_map(fn (array $item) => [
                    'label' => $item['label'],
                    'url' => $item['url'],
                    'subtitle' => $item['subtitle'],
                    // Classe d'icône Themify (ti-*) de la feuille, pour que le consommateur (Moodle)
                    // rende le même pictogramme que l'entête. Défensif : une entrée sans icône
                    // renvoie une chaîne vide plutôt que de lever un warning qui casserait l'API.
                    'icon' => $item['icon'] ?? '',
                ], $group['items']),
            ], $node['children']);
        }

        if (isset($node['cta'])) {
            $out['cta'] = $node['cta'];
        }

        return $out;
    }

    /** @return list<array{label:string,url:string}> */
    private function legalLinks(): array
    {
        $links = [];
        foreach ([
            'legal.privacy' => __('Confidentialité'),
            'legal.terms' => __("Conditions d'utilisation"),
            'legal.sales' => __('Conditions de vente'),
            'legal.cookies' => __('Cookies'),
            'legal.rights' => __('Exercer mes droits'),
        ] as $route => $label) {
            if (Route::has($route)) {
                $links[] = ['label' => $label, 'url' => route($route)];
            }
        }

        return $links;
    }
}
