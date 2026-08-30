<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Auto-linking glossaire/acronymes (Memora #141, 2026-05-05).
 *
 * Détecte les termes du Dictionary + Acronyms dans un HTML et insère :
 *  - <a class="glossary-link" href="/glossaire/X" data-tooltip="...">terme</a>
 *  - first-occurrence-only par appel
 *  - skip h1-h6, code, pre, a, abbr, blockquote, dfn, label
 *  - skip self-linking sur la page du terme lui-même
 *
 * Performance : Trie virtuel via regex compilée (sorted by length desc).
 * Cache 1h via Model events (Term + Acronym).
 *
 * Usage:
 *   {!! GlossaryLinkifier::linkify($article->summary) !!}
 *   $matched = GlossaryLinkifier::extractMatchedTerms($html); // pour Schema.org
 */
class GlossaryLinkifier
{
    public const CACHE_KEY = 'glossary.terms.v14.'; // 2026-08-29 bump v14 : ALIAS_NEVER_AUTO (+ « dos », mesuré 3 faux sur 3) - sans ce bump, un cache v12 déjà chaud servirait 1h de plus des entrées SANS cette exclusion, donc les mêmes faux liens
    public const CACHE_TTL = 3600; // 1h
    // 2026-08-02 #1526 : compteur d'epoch pour invalider le cache du RÉSULTAT linkify() (voir linkify()
    // et flushCache()) sans avoir à énumérer des clés — un seul Cache::forever() invalide tout d'un coup.
    public const TERMS_EPOCH_KEY = 'glossary.terms.epoch';
    public const MIN_LENGTH = 3; // #153 bump : 4→3 pour permettre TPU/GPU/NPU. Garde 2 chars rejetés via acronyme tout-cap check
    public const MAX_LINKS_PER_PAGE = 120; // #158 bump 60→120 : articles longs (S90 concentré 20 URLs) saturent à 60. 120 couvre 80%+ occurrences
    public const MAX_OCCURRENCES_PER_TERM = 10; // #158 wrap jusqu'à 10× le même terme par page (vs 1× avant)


    /**
     * 2026-08-21 (demande fondateur, après audit exhaustif) : ORIGINE d'une entrée de matching.
     * Une même chaîne peut être revendiquée par plusieurs fiches - par exemple « Modèle multimodal »
     * est le NOM PRINCIPAL de /glossaire/modele-multimodal ET un simple ALIAS de
     * /glossaire/ia-multimodale. L'intention éditoriale veut que le nom principal l'emporte : c'est
     * la fiche qui porte réellement ce titre. Ce rang sert de critère de tri (voir loadTerms()),
     * APRÈS la longueur et la spécificité de stratégie.
     *   0 = nom principal d'une fiche (glossaire, acronyme, outil)
     *   1 = alias CURÉ à la main par l'équipe (colonne `aliases`)
     *   2 = alias AUTO-DÉRIVÉ par le code (pluriel FR, variante de casse, qualifier « X (Y) »)
     */
    public const ORIGIN_PRIMARY = 0;

    public const ORIGIN_CURATED_ALIAS = 1;

    public const ORIGIN_DERIVED_ALIAS = 2;
    /**
     * 2026-05-05 #145 WSD : stop-list FR baseline (verbes/noms communs polysémiques).
     * Si terme glossaire matche un mot de cette liste ET que sa strategy est 'loose',
     * on escalade automatiquement vers 'case_sensitive' (le terme glossaire doit etre tel quel).
     * Editeur peut toujours forcer 'loose' via match_strategy='loose' explicite (ignore stop-list).
     */
    public const STOP_LIST_FR = [
        // Verbes courants polysémiques avec termes IA
        'transformer', 'générer', 'modeler', 'agent', 'agents',
        'modèle', 'modèles', 'vision', 'attention', 'mémoire',
        'apprentissage', 'plateforme', 'plateformes', 'architecture',
        'fonction', 'fonctions', 'classe', 'classes', 'objet', 'objets',
        'instance', 'instances', 'token', 'tokens', 'fenêtre', 'fenêtres',
        'cadre', 'cadres', 'interface', 'interfaces', 'pipeline', 'pipelines',
        'service', 'services', 'composant', 'composants', 'module', 'modules',
    ];

    /**
     * 2026-06-17 #163 : homographes EMPRUNTÉS (token, pipeline) dont la forme MINUSCULE est le terme
     * légitime (≠ mot/verbe FR courant). On NE force PAS la casse stricte pour eux : la minuscule garde
     * son lien (intention éditoriale, alias minuscules curés). Distingue « token » (terme) de
     * « transformer » (verbe FR à ne PAS lier en minuscule).
     */
    public const HOMOGRAPH_LOWERCASE_OK = ['token', 'tokens', 'pipeline', 'pipelines'];

    /**
     * 2026-06-17 #164 : noms d'outils de l'annuaire qui sont aussi des mots courants / prénoms
     * (« Claude », « Avec », « Tome », « Make »…) → JAMAIS auto-liés, sinon faux positifs en prose FR.
     * Les ~330 autres outils (noms distinctifs) sont auto-liés en casse stricte vers /annuaire/{slug}.
     *
     * 2026-08-28 : cette liste protège bien l'auto-lien du CORPS DE TEXTE (loadTerms() plus bas
     * exclut ces noms de $terms), mais NewsToolSyncAction::suggest() la parcourt aussi dans l'autre
     * sens pour RECAPTURER un nom qui apparaît avec une majuscule initiale - intention légitime
     * pour « Avec », « Tome », « Make » (rattraper l'outil quand il est réellement cité, une
     * majuscule en tête de phrase y étant rarissime pour un mot français aussi courant). Pour le
     * sous-ensemble ci-dessous, la majuscule ne prouve RIEN (début de titre, fragment d'un autre nom
     * propre) : voir TOOL_NEVER_RECAPTURE, injecté ici par unpacking pour ne définir cette liste
     * qu'à un seul endroit.
     */
    public const TOOL_NEVER_AUTO = ['claude', 'avec', 'tome', 'caribou', 'make', 'motion', 'gamma', 'gemini', 'mistral', 'consensus', 'intent', 'dust', 'soar', 'remind', 'spinach', 'grok', 'aqua', 'handy', 'lounge', 'willow', 'poe', 'pika', 'noa', 'deduce',
        // 2026-08-28 : le reste (mots jamais recapturables, quelle que soit la casse observée)
        // vit dans UNE seule définition, TOOL_NEVER_RECAPTURE ci-dessous - jamais recopiée ici.
        ...self::TOOL_NEVER_RECAPTURE];

    /**
     * 2026-08-28 (fermeture de faille, backfill outil↔actualité) : noms qu'AUCUN contexte
     * typographique - pas même une majuscule initiale - ne distingue du mot commun ou du fragment
     * d'un autre nom propre. Contrairement au reste de TOOL_NEVER_AUTO (où une majuscule signale
     * une vraie mention, ex. « Avec », « Tome », « Make »), une majuscule ici ne prouve rien : elle
     * vient aussi bien d'un début de titre (« Local AI »), d'un fragment d'un AUTRE nom propre
     * (« Global AI Pulse » de KPMG, « Thrive Logic »), ou d'un mot français ordinaire que la
     * typographie capitalise pour toute autre raison (début de phrase, titre).
     *
     * Défaut mesuré le 2026-08-28 : NewsToolSyncAction::suggest() parcourait TOOL_NEVER_AUTO et
     * recapturait tout nom présent avec une majuscule initiale dans le texte - sur un backfill de
     * 33 liens outil↔actualité, 4 étaient faux (12 %), tous par ce mécanisme : « Local AI » en tête
     * de titre → outil « Local »; « Montage vidéo par lots » → outil « Montage »; « Global AI
     * Pulse » (KPMG) → outil « Pulse »; « Thrive Logic » → outil « Logic ». Ajouter un nom à
     * TOOL_NEVER_AUTO le bloquait donc d'un côté (corps de texte) et le laissait entrer par la
     * porte d'à côté (suggest()). Les noms d'ici sont exclus du mécanisme de recapture de
     * suggest(), SANS EXCEPTION - voir NewsToolSyncAction::suggest().
     *
     * Liste vérifiée nom par nom (analyse initiale sur 2 221 outils publiés, pas une vérité reçue
     * telle quelle) : 3 candidats reçus ont été ÉCARTÉS faute de collision réaliste en français -
     * « brew » (Homebrew est un seul mot soudé, sans frontière avant « brew »; aucun usage courant
     * du mot isolé); « pioneer » (le français dit « pionnier », orthographe différente = pas de
     * collision de graphie); « needle » (le français dit « aiguille », même raison). Un nom
     * réellement distinctif ne doit PAS figurer ici, sous peine de priver ce mécanisme de son
     * intérêt.
     */
    public const TOOL_NEVER_RECAPTURE = [
        // 2026-08-28 (demande fondateur, 4 faux positifs mesurés) :
        'local', 'montage', 'pulse', 'logic',
        // 2026-08-28 : candidats gravité très élevée, vérifiés - mot français courant ou terme
        // technique d'orthographe IDENTIQUE en français, capitalisable en tête de phrase/titre
        // sans lien avec l'outil (ex. « flux » de nouvelles, « Aider » verbe français ultra-courant
        // et cas d'école du mandat, « Keep » qui collisionne avec Google Keep, « Quest » avec Meta
        // Quest, « Vitals » avec Core Web Vitals) :
        'flux', 'studio', 'volume', 'runtime', 'aider', 'box', 'quest', 'vitals', 'macro', 'bolt', 'keep',
        // 2026-08-28 : candidats gravité élevée, vérifiés - même famille (ex. « Forge » que ce
        // projet même emploie pour sa propre infrastructure, « Radar » dans l'expression « rester
        // sous le radar », « Retina » qui collisionne avec l'écran Retina d'Apple, « Prism » avec le
        // programme de surveillance PRISM, « Mira » avec Mira Murati, ex-CTO d'OpenAI, « Fred »
        // comme prénom courant) :
        'draft', 'brief', 'forge', 'handler', 'deck', 'shadow', 'mute', 'bastion', 'cadence', 'campus',
        'metal', 'prism', 'radar', 'retina', 'epic', 'fred', 'mira',
    ];

    /**
     * 2026-08-28 (défaut mesuré en production, fiche « libreoffice-268-... ») : noms d'outils
     * dont la mention SEULE est légitime (contrairement à TOOL_NEVER_AUTO/TOOL_NEVER_RECAPTURE,
     * PAS de blocage total) mais qui, précédés d'un mot précis, forment un FAUX COMPOSÉ - une
     * expression réelle et différente qui n'a rien à voir avec l'outil.
     *
     * LibreOffice 26.8 a une fonctionnalité nommée « Paragraph Composer » (moteur de composition
     * typographique, RIEN à voir avec l'IA) sur une fiche dont la thèse entière est l'ABSENCE
     * d'IA générative. Le linkifier capturait « Composer » à l'intérieur de cette expression et
     * posait <a href="/annuaire/composer"> vers l'outil IA homonyme ; NewsToolSyncAction::suggest()
     * consomme ce MÊME matching (getLastMatchedTerms()) et attachait donc aussi l'outil en
     * source=auto - les deux mécanismes sont CONVERGENTS ici (une seule cause, un seul correctif).
     *
     * « composer » n'a par ailleurs aucune raison d'être dans TOOL_NEVER_AUTO : contrairement à
     * « claude », « avec », « tome »… ce n'est PAS un mot français courant en prose ordinaire, donc
     * un blocage total priverait le site d'auto-liens légitimes pour l'outil Composer employé seul.
     *
     * Implémenté comme lookbehind négatif directement dans le pattern (matchInText()) : le mot
     * exclu juste avant ne matche jamais, mais un « Composer » plus loin dans le MÊME texte (ou
     * ailleurs sur le site) continue d'être capturé normalement - aucune régression sur les
     * mentions légitimes. Clé = nom d'outil en minuscules ; valeur = mots-préfixes (comparaison
     * insensible à la casse) qui invalident le match s'ils précèdent immédiatement le terme.
     */
    public const TOOL_COMPOUND_EXCLUSIONS = [
        'composer' => ['paragraph'],
    ];

    /**
     * 2026-05-05 #141 b : tracking cumulatif inter-appels.
     * Une page peut appeler @glossarize() plusieurs fois (hook, key_points, why_important, etc.).
     * On veut first-occurrence GLOBAL et accumulation des matched terms pour Schema.org.
     * Reset automatique au prochain cycle de requête (singleton naturel Laravel).
     */
    protected static array $matchedThisRequest = [];
    protected static array $seenThisRequest = [];
    protected static int $linkCountThisRequest = 0;

    /**
     * Auto-linkify un HTML : injecte des liens vers Dictionary/Acronyms.
     */
    public static function linkify(?string $html, array $options = []): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $skipSlug = $options['skip_slug'] ?? null;
        $maxLinks = $options['max_links'] ?? self::MAX_LINKS_PER_PAGE;
        $maxOcc = $options['max_occ'] ?? self::MAX_OCCURRENCES_PER_TERM; // 2026-05-26 #300 : opt option pour pages glossaire (1 occurrence par terme)
        // 2026-07-25 #1350 : perSection=true -> 1 occurrence par terme PAR SECTION H2 (moins agressant
        // visuellement) au lieu de $maxOcc par article entier. Opt-in via $options pour ne rien changer
        // aux appels existants (pages glossaire, actualités, etc.).
        $perSection = $options['per_section'] ?? false;
        $currentSection = 0;

        $terms = self::loadTerms();
        if (empty($terms)) {
            return $html;
        }

        // Tracking cumulatif inter-appels (par requête HTTP)

        // 2026-08-02 #1526 : mesure en prod (probes) = 5-7s par rendu de /annuaire/{slug} entièrement
        // dans cette boucle de matching (24ms de SQL cumulé sur 81 requêtes, donc pas la BD). On cache
        // le RÉSULTAT (HTML final + état matched/seen/linkCount) UNIQUEMENT pour le 1er appel @glossarize()
        // de la requête : le docblock de tracking cumulatif ci-dessus (self::$seenThisRequest) prouve
        // qu'un appel qui n'est PAS le premier dépend de l'état laissé par les appels précédents sur la
        // MÊME page (hook, key_points, why_important...) — donc pas cache-able en isolation. Le cas
        // dominant (Directory/show.blade.php : 1 seul appel @glossarize()) profite pleinement du cache.
        $cacheEligible = empty(self::$seenThisRequest) && self::$linkCountThisRequest === 0;
        $resultCacheKey = null;
        if ($cacheEligible) {
            $resultCacheKey = 'glossary.linkify.result.'.md5(implode('|', [
                $html,
                (string) $skipSlug,
                (string) $maxLinks,
                (string) $maxOcc,
                $perSection ? '1' : '0',
                app()->getLocale() ?: 'fr_CA',
                (string) self::currentTermsEpoch(),
            ]));

            $cached = Cache::get($resultCacheKey);
            if (is_array($cached) && array_key_exists('html', $cached) && array_key_exists('seen', $cached) && array_key_exists('linkCount', $cached) && array_key_exists('matched', $cached)) {
                self::$seenThisRequest = $cached['seen'];
                self::$linkCountThisRequest = $cached['linkCount'];
                foreach ($cached['matched'] as $slug => $term) {
                    self::$matchedThisRequest[$slug] = $term;
                }

                return $cached['html'];
            }
        }

        try {
            $dom = new \DOMDocument;
            // Charset trick: force UTF-8 + suppress HTML5 warnings
            libxml_use_internal_errors(true);
            $wrapped = '<?xml encoding="UTF-8"?><div id="glx-root">'.$html.'</div>';
            $dom->loadHTML($wrapped, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();
        } catch (\Throwable $e) {
            Log::warning('GlossaryLinkifier::linkify - DOM parse fail', ['msg' => $e->getMessage()]);
            return $html;
        }

        $root = $dom->getElementById('glx-root');
        if (! $root) {
            return $html;
        }

        self::walkAndReplace($dom, $root, $terms, self::$seenThisRequest, self::$linkCountThisRequest, $maxLinks, $skipSlug, $maxOcc, $perSection, $currentSection);

        // Extract inner HTML from glx-root wrapper
        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        // 2026-08-02 #1526 : écrit le cache résultat (voir bloc de lecture plus haut) uniquement si cet
        // appel était éligible (1er appel de la requête) — sinon on figerait un état partiel/cumulatif faux.
        if ($cacheEligible && $resultCacheKey !== null) {
            Cache::put($resultCacheKey, [
                'html' => $output,
                'seen' => self::$seenThisRequest,
                'linkCount' => self::$linkCountThisRequest,
                'matched' => self::$matchedThisRequest,
            ], self::CACHE_TTL);
        }

        return $output;
    }

    /**
     * Récupère les termes matchés pendant le dernier linkify() appel.
     * Utilisé par Schema.org JSON-LD pour générer DefinedTermSet.
     *
     * @return array<int, array{name:string, slug:string, definition:string, type:string, url:string}>
     */
    public static function getLastMatchedTerms(): array
    {
        return array_values(self::$matchedThisRequest);
    }

    /**
     * Reset state (utile pour Octane / jobs queue / tests).
     */
    public static function resetState(): void
    {
        self::$matchedThisRequest = [];
        self::$seenThisRequest = [];
        self::$linkCountThisRequest = 0;
    }

    /**
     * Charge les termes Dictionary + Acronyms depuis cache (1h TTL).
     * Sorted by length DESC pour matcher les expressions longues en premier.
     */
    public static function loadTerms(): array
    {
        $locale = app()->getLocale() ?: 'fr_CA';

        return Cache::remember(self::CACHE_KEY.$locale, self::CACHE_TTL, function () use ($locale) {
            $terms = [];

            // Dictionary
            if (class_exists(\Modules\Dictionary\Models\Term::class)) {
                try {
                    \Modules\Dictionary\Models\Term::published()
                        ->select(['id', 'name', 'slug', 'definition', 'type', 'match_strategy', 'aliases'])
                        ->get()
                        ->each(function ($t) use (&$terms, $locale) {
                            $name = $t->getTranslation('name', $locale, false) ?: $t->name;
                            $slug = $t->getTranslation('slug', $locale, false) ?: $t->slug;
                            $def = $t->getTranslation('definition', $locale, false) ?: $t->definition;
                            if (! $name || ! $slug || mb_strlen($name) < self::MIN_LENGTH) return;
                            $strategy = $t->match_strategy ?? 'loose';
                            if ($strategy === 'never_auto') return; // 2026-05-05 #145 : opt-out
                            // 2026-05-11 #153 : acronymes courts (3-4 chars tout-cap) → force case_sensitive
                            // pour éviter faux positifs en mode loose (ex "tpu" lowercase dans texte arbitraire).
                            if (mb_strlen($name) <= 4 && preg_match('/^[A-Z0-9]{3,4}$/u', $name)) {
                                $strategy = 'case_sensitive';
                            }
                            $strategy = self::escalateStrategyIfStopList($name, $strategy); // auto-escalade si polysémique
                            $shortDef = Str::limit(self::stripMarkdownInline(strip_tags((string) $def)), 180);
                            $url = '/glossaire/'.$slug;
                            // 2026-05-05 #150+#151 : entry pour le terme principal + entries pour chaque alias
                            $terms[] = [
                                'name' => $name, 'slug' => $slug, 'definition' => $shortDef,
                                'type' => 'glossary', 'url' => $url, 'match_strategy' => $strategy,
                                'origin_rank' => self::ORIGIN_PRIMARY,
                            ];
                            // 2026-05-11 #138 : aliases manuels DB
                            $aliases = is_array($t->aliases) ? $t->aliases : (is_string($t->aliases) ? json_decode($t->aliases, true) : []);
                            if (is_array($aliases)) {
                                foreach ($aliases as $alias) {
                                    if (! is_string($alias) || mb_strlen($alias) < 2) continue;
                                    if (self::isNeverAutoAlias($alias)) continue; // 2026-08-29 : voir ALIAS_NEVER_AUTO (ex. « requête », « témoin »)
                                    $terms[] = [
                                        'name' => $alias, 'slug' => $slug, 'definition' => $shortDef,
                                        'type' => 'glossary', 'url' => $url,
                                        'match_strategy' => self::escalateStrategyIfStopList($alias, $strategy),
                                        'origin_rank' => self::ORIGIN_CURATED_ALIAS,
                                    ];
                                }
                            }
                            // 2026-05-11 #138 : auto-extract qualifier "X (Y)" → aliases dérivés
                            foreach (self::extractQualifierAliases($name) as $autoAlias) {
                                if (mb_strlen($autoAlias) < 2) continue;
                                if (self::isNeverAutoAlias($autoAlias)) continue; // 2026-08-29 : voir ALIAS_NEVER_AUTO (ex. « CNN »)
                                $terms[] = [
                                    'name' => $autoAlias, 'slug' => $slug, 'definition' => $shortDef,
                                    'type' => 'glossary', 'url' => $url,
                                    'match_strategy' => self::escalateStrategyIfStopList($autoAlias, $strategy),
                                    'origin_rank' => self::ORIGIN_DERIVED_ALIAS,
                                ];
                            }
                            // 2026-05-11 #146 Phase A : aliases morphologiques FR (pluriel + casse)
                            $morphoBase = $name;
                            // Si le name a un qualifier, applique morpho sur la base extraite aussi
                            foreach (array_merge([$morphoBase], self::extractQualifierAliases($name)) as $candidate) {
                                foreach (self::extractMorphologicalAliases($candidate) as $morpho) {
                                    if (mb_strlen($morpho) < self::MIN_LENGTH) continue;
                                    if (self::isNeverAutoAlias($morpho)) continue; // 2026-08-29 : voir ALIAS_NEVER_AUTO
                                    $terms[] = [
                                        'name' => $morpho, 'slug' => $slug, 'definition' => $shortDef,
                                        'type' => 'glossary', 'url' => $url,
                                        'match_strategy' => self::escalateStrategyIfStopList($morpho, $strategy),
                                        'origin_rank' => self::ORIGIN_DERIVED_ALIAS,
                                    ];
                                }
                            }
                        });
                } catch (\Throwable $e) {
                    Log::warning('GlossaryLinkifier - Term load fail', ['e' => $e->getMessage()]);
                }
            }

            // Acronyms (matche acronym ET full_name + aliases)
            // ACTION: regroupement des sigles homonymes vers page de désambiguïsation
            // MCP: Hermes→qwen3-max | RAISON: sigle ambigu (N sens) → 1 seule entrée linkifier → /disambiguate/{sigle}
            if (class_exists(\Modules\Acronyms\Models\Acronym::class)) {
                try {
                    // Tableau temporaire : [strtolower($acro)] → [entries candidats pour le sigle court]
                    $acrGrouped = [];

                    \Modules\Acronyms\Models\Acronym::published()
                        ->select(['id', 'acronym', 'full_name', 'slug', 'description', 'match_strategy', 'aliases'])
                        ->get()
                        ->each(function ($a) use (&$terms, &$acrGrouped, $locale) {
                            $acro = $a->getTranslation('acronym', $locale, false) ?: $a->acronym;
                            $full = $a->getTranslation('full_name', $locale, false) ?: $a->full_name;
                            $slug = $a->getTranslation('slug', $locale, false) ?: $a->slug;
                            $desc = $a->getTranslation('description', $locale, false) ?: $a->description;
                            if (! $slug) return;
                            $strategy = $a->match_strategy ?? 'case_sensitive';
                            if ($strategy === 'never_auto') return;
                            $url = '/acronymes-education/'.$slug;
                            $shortDesc = Str::limit(self::stripMarkdownInline(strip_tags((string) $desc)), 180);

                            // Forme longue (ex "Observatoire de l'IA et du numérique") → URL individuelle toujours
                            if ($full && mb_strlen($full) >= self::MIN_LENGTH) {
                                $fullStrategy = self::escalateStrategyIfStopList($full, $strategy === 'case_sensitive' ? 'loose' : $strategy);
                                $terms[] = [
                                    'name' => $full, 'slug' => $slug, 'definition' => $shortDesc,
                                    'type' => 'acronym_full', 'url' => $url, 'match_strategy' => $fullStrategy,
                                    'origin_rank' => self::ORIGIN_PRIMARY,
                                ];
                            }
                            // 2026-05-05 #151 : aliases (variations) avec strategy heritee → URL individuelle
                            $aliases = is_array($a->aliases) ? $a->aliases : (is_string($a->aliases) ? json_decode($a->aliases, true) : []);
                            if (is_array($aliases)) {
                                foreach ($aliases as $alias) {
                                    if (! is_string($alias) || mb_strlen($alias) < 2) continue;
                                    if (self::isNeverAutoAlias($alias)) continue; // 2026-08-29 : voir ALIAS_NEVER_AUTO
                                    $terms[] = [
                                        'name' => $alias, 'slug' => $slug, 'definition' => $shortDesc,
                                        'type' => 'acronym_alias', 'url' => $url,
                                        'match_strategy' => self::escalateStrategyIfStopList($alias, $strategy),
                                        'origin_rank' => self::ORIGIN_CURATED_ALIAS,
                                    ];
                                }
                            }
                            // 2026-05-11 #138 : auto-extract qualifier "X (Y)" sur full_name → URL individuelle
                            if ($full) {
                                foreach (self::extractQualifierAliases($full) as $autoAlias) {
                                    if (mb_strlen($autoAlias) < 2) continue;
                                    if (self::isNeverAutoAlias($autoAlias)) continue; // 2026-08-29 : voir ALIAS_NEVER_AUTO
                                    $terms[] = [
                                        'name' => $autoAlias, 'slug' => $slug, 'definition' => $shortDesc,
                                        'type' => 'acronym_alias', 'url' => $url,
                                        'match_strategy' => self::escalateStrategyIfStopList($autoAlias, $strategy === 'case_sensitive' ? 'loose' : $strategy),
                                        'origin_rank' => self::ORIGIN_DERIVED_ALIAS,
                                    ];
                                }
                            }
                            // Sigle court : regrouper par clé normalisée (minuscules)
                            // La résolution ambiguïté se fait APRÈS la boucle complète
                            if ($acro && mb_strlen($acro) >= 2) {
                                $acrKey = strtolower($acro);
                                $acrGrouped[$acrKey][] = [
                                    'name' => $acro, 'slug' => $slug,
                                    'definition' => $full ? "{$full} : {$shortDesc}" : $shortDesc,
                                    'type' => 'acronym', 'url' => $url, 'match_strategy' => $strategy,
                                    'origin_rank' => self::ORIGIN_PRIMARY,
                                ];
                            }
                        });

                    // Résolution des sigles courts : 1 fiche → direct ; N fiches → désambiguïsation
                    foreach ($acrGrouped as $acrKey => $candidates) {
                        $resolvedUrl = self::resolveAmbiguousAcronymUrl($candidates[0]['name'], $candidates);
                        if (count($candidates) === 1) {
                            // Sigle non ambigu : comportement original préservé
                            $terms[] = $candidates[0];
                        } else {
                            // Sigle ambigu : une seule entrée linkifier → page de désambiguïsation
                            $terms[] = [
                                'name' => $candidates[0]['name'],
                                'slug' => 'disambiguate-'.$acrKey,
                                'definition' => 'Sigle ambigu – '.count($candidates).' significations. Cliquez pour choisir.',
                                'type' => 'acronym',
                                'url' => $resolvedUrl,
                                'match_strategy' => 'case_sensitive', // strict pour les sigles
                                'origin_rank' => self::ORIGIN_PRIMARY,
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('GlossaryLinkifier - Acronym load fail', ['e' => $e->getMessage()]);
                }
            }

            // 2026-06-17 #164 : 3e source — OUTILS DE L'ANNUAIRE (Directory). Auto-lien vers /annuaire/{slug}.
            // Glossaire + acronymes ont la PRIORITÉ (déjà dans $terms) ; on ne double pas un nom déjà pris.
            $takenLower = [];
            foreach ($terms as $tt) {
                $takenLower[mb_strtolower($tt['name'])] = true;
            }

            if (class_exists(\Modules\Directory\Models\Tool::class)) {
                try {
                    \Modules\Directory\Models\Tool::query()
                        ->where('status', 'published')
                        ->get(['id', 'name', 'slug', 'short_description', 'aliases'])
                        ->each(function ($tool) use (&$terms, &$takenLower, $locale) {
                            try {
                                $name = $tool->getTranslation('name', $locale, false) ?: $tool->name;
                                $slug = $tool->getTranslation('slug', $locale, false) ?: $tool->slug;
                                if (! $name || ! $slug) {
                                    return;
                                }
                                if (mb_strlen($name) < self::MIN_LENGTH) {
                                    return;
                                }
                                $lower = mb_strtolower($name);
                                if (in_array($lower, self::TOOL_NEVER_AUTO, true)) {
                                    return; // mot courant / prénom → jamais auto-lié
                                }
                                if (isset($takenLower[$lower])) {
                                    return; // précédence glossaire/acronyme (ou doublon outil)
                                }
                                $desc = $tool->getTranslation('short_description', $locale, false) ?: $tool->short_description;
                                $shortDesc = Str::limit(self::stripMarkdownInline(strip_tags((string) $desc)), 180);
                                $url = '/annuaire/'.$slug;
                                $terms[] = [
                                    'name' => $name,
                                    'slug' => $slug,
                                    'definition' => $shortDesc,
                                    'type' => 'tool',
                                    'url' => $url,
                                    'match_strategy' => 'case_sensitive',
                                    'origin_rank' => self::ORIGIN_PRIMARY,
                                    // 2026-08-28 : faux composés (« Paragraph Composer ») - voir TOOL_COMPOUND_EXCLUSIONS.
                                    'exclude_after' => self::TOOL_COMPOUND_EXCLUSIONS[$lower] ?? [],
                                ];
                                $takenLower[$lower] = true;

                                // 2026-06-29 : aliases de l'outil → même URL, même stratégie case_sensitive.
                                // Même garde-fous que le nom principal (TOOL_NEVER_AUTO, longueur minimale,
                                // précédence glossaire/acronyme/nom-outil déjà pris).
                                if (is_array($tool->aliases)) {
                                    foreach ($tool->aliases as $alias) {
                                        if (! is_string($alias) || mb_strlen(trim($alias)) < self::MIN_LENGTH) {
                                            continue;
                                        }
                                        $aliasLower = mb_strtolower(trim($alias));
                                        if (in_array($aliasLower, self::TOOL_NEVER_AUTO, true)) {
                                            continue;
                                        }
                                        if (isset($takenLower[$aliasLower])) {
                                            continue; // glossaire/acronyme ou autre outil a priorité
                                        }
                                        $terms[] = [
                                            'name'           => trim($alias),
                                            'slug'           => $slug,
                                            'definition'     => $shortDesc,
                                            'type'           => 'tool_alias',
                                            'url'            => $url,
                                            'match_strategy' => 'case_sensitive',
                                            'origin_rank'    => self::ORIGIN_CURATED_ALIAS,
                                        ];
                                        $takenLower[$aliasLower] = true;
                                    }
                                }
                            } catch (\Throwable $e) {
                                return;
                            }
                        });
                } catch (\Throwable $e) {
                    Log::warning('GlossaryLinkifier - Tool load fail', ['e' => $e->getMessage()]);
                }
            }

            // Sort par longueur DESC (matche les expressions longues en priorité), PUIS par
            // SPÉCIFICITÉ DE STRATÉGIE à longueur égale (2026-08-21, demande fondateur ; panel :
            // Gemini 3.1 Pro « recommandation 1 », la seule sans coût de parcours DOM).
            //
            // Bug mesuré en prod AVANT ce tri : à longueur égale, l'ordre entre deux entrées était
            // indéterminé, donc une entrée `loose` (souvent un ALIAS auto-dérivé, insensible à la
            // casse) pouvait passer devant l'entrée `case_sensitive` dont la casse est EXACTEMENT
            // celle du texte. Conséquences constatées sur des pages réelles :
            //   « xAI » (entreprise d'Elon Musk) → /glossaire/ia-explicable   [alias loose « XAI »]
            //   « IA »  (générique)              → /glossaire/autonomie-ia    [alias loose « IA »]
            // Désormais, à longueur égale, la stratégie la plus stricte gagne : case_sensitive (0)
            // avant partial_case_sensitive (1) avant loose (2). Une entrée case_sensitive n'a par
            // définition PAS d'effet sur un texte dont la casse diffère (sa regex ne matche pas) :
            // le repli sur l'entrée loose reste donc possible, ce qui rend ce tri non régressif
            // pour les 5000+ autres entrées (« l'api rest » continue de matcher l'alias loose).
            // Piste explicitement ÉCARTÉE (Gemini) : exiger la casse exacte pour tout terme court
            // (<= 4 caractères) casserait « LE FUTUR DE L'IA », « une api rest », « Ia générative ».
            $strategyRank = static fn (array $t): int => match ($t['match_strategy'] ?? 'loose') {
                'case_sensitive', 'exact_phrase' => 0,
                'partial_case_sensitive' => 1,
                default => 2,
            };
            // 3e critère (2026-08-21) : à longueur ET stratégie égales, le NOM PRINCIPAL d'une fiche
            // bat un alias curé, qui bat lui-même un alias auto-dérivé (voir ORIGIN_* plus haut).
            // Mesuré : ce seul critère résout 7 des 11 collisions restantes de l'audit, et surtout
            // il PRÉVIENT les suivantes sans intervention humaine.
            usort($terms, function ($a, $b) use ($strategyRank) {
                return (mb_strlen($b['name']) <=> mb_strlen($a['name']))
                    ?: ($strategyRank($a) <=> $strategyRank($b))
                    ?: (($a['origin_rank'] ?? self::ORIGIN_DERIVED_ALIAS) <=> ($b['origin_rank'] ?? self::ORIGIN_DERIVED_ALIAS));
            });

            return $terms;
        });
    }

    /**
     * Organisations qui n'apparaissent en qualifier QUE pour lever une ambiguïté.
     *
     * 2026-08-23 : « Gemini (Google) » promouvait « Google » en synonyme de Gemini, si bien que
     * CHAQUE mention de l'entreprise Google, partout sur le site, renvoyait le lecteur vers la
     * fiche du modèle Gemini. Mesuré sur une actualité qui parlait de Sundar Pichai et du code
     * de Google : 6 liens, tous faux. Trois autres termes portaient le même défaut :
     * « Claude (Anthropic) », « Grok (xAI) » et « Llama (Meta) ».
     *
     * Le motif fautif était `[A-Z][a-zA-Z]{1,9}`, écrit pour capter des noms de techniques
     * (ReAct, Adam) : il capte tout aussi bien Google, Meta, Apple, Nvidia ou Mistral.
     *
     * La règle de fond : **un qualifier qui nomme le FABRICANT est un désambiguïsateur, pas un
     * synonyme.** Personne qui écrit « Google » ne parle de Gemini ; on précise « (Google) »
     * justement parce que le mot « Gemini » seul serait ambigu. L'inverse n'est pas vrai.
     * Un acronyme technique en majuscules (CNN, GAN, RNN), lui, EST bien un synonyme du terme.
     *
     * Liste explicite plutôt que devinette : elle se lit, se grep et s'étend en une ligne.
     * Comparaison insensible à la casse.
     */
    public const QUALIFIER_ORGANISATION = [
        'google', 'anthropic', 'openai', 'meta', 'microsoft', 'apple', 'amazon',
        'nvidia', 'mistral', 'adobe', 'ibm', 'deepseek', 'alibaba',
        'perplexity', 'stability', 'cohere', 'huggingface', 'salesforce',
    ];

    /**
     * ABSENT DÉLIBÉRÉMENT de la liste ci-dessus : « xAI ».
     *
     * La chaîne « XAI » a deux sens sur ce site. Dans « Grok (xAI) » elle nomme l'entreprise ;
     * dans « Explicabilité (XAI) » elle abrège *eXplainable AI*, et c'est là un vrai synonyme du
     * terme, que le lecteur cherchera sous cette forme. L'inscrire dans la liste casserait le
     * second cas pour réparer le premier. Le départage entre les deux est déjà assuré par le tri
     * par spécificité de buildTerms(), et un terme dédié « xAI » existe au glossaire : c'est lui
     * qui gagne sur une mention isolée de l'entreprise. Homographe connu, traité ailleurs.
     */

    /**
     * 2026-08-29 (deux faux liens MESURÉS en production le même jour) : un alias, une fois entré
     * dans la base - à la main via la colonne `aliases`, ou dérivé automatiquement par
     * extractQualifierAliases() - est considéré fiable pour toujours, sur tout le site, quel que
     * soit le sujet du texte qui l'entoure. Rien ne vérifie que sa forme n'est pas AUSSI un mot ou
     * un sigle courant hors du domaine technique. Deux mécanismes distincts produisent la même
     * famille de défaut ; cette liste les corrige tous les deux au même endroit.
     *
     * Cas 1 - alias AUTO-DÉRIVÉ : « Réseau convolutif (CNN) » fait exactement ce que
     * extractQualifierAliases() doit faire (voir son docblock : un acronyme technique tout-cap
     * EST un synonyme du terme) - la fonction n'a pas de défaut, la règle générale reste juste
     * pour GAN, RNN, NAS. C'est CNN précisément qui porte, hors de ce site, un second sens bien
     * plus répandu (le réseau de télévision). Sur une actualité de journalisme, « CNN » s'est
     * retrouvé lié quatre fois vers /glossaire/reseau-convolutif.
     *
     * Cas 2 - alias CURÉ À LA MAIN : « requête »/« requêtes » ont été ajoutés le 2026-07-23 comme
     * synonymes courants de « prompt » (migration add_requete_alias_to_prompt_term). Vrai en
     * contexte IA, mais « requête » est un nom commun français omniprésent hors de ce contexte
     * (une requête en rejet, une requête introductive d'instance, une requête SQL...). Sur une
     * actualité de droit, « une requête en rejet » s'est retrouvé lié vers /glossaire/prompt.
     *
     * Cas 3 - même motif, repéré par l'audit AVANT incident (pas encore mesuré en production,
     * corrigé par précaution) : « témoin » est l'alias curé de « cookie » (témoin de connexion)
     * depuis le lot du 2026-06-13, et c'est aussi le mot français ordinaire pour une personne qui
     * témoigne (un témoin a déclaré..., témoin oculaire...).
     *
     * Ni la base (fiable, mais aveugle au monde extérieur) ni extractQualifierAliases()
     * (structurellement saine) ne portent la connaissance « ce mot a un autre sens dominant » :
     * elle vit ici, à part, comme QUALIFIER_ORGANISATION ci-dessus - curée à la main, jamais
     * devinée par une règle générale. Bloque l'entrée à l'insertion dans $terms quelle que soit
     * son origine (alias curé, alias dérivé d'un qualifier, variante morphologique) - JAMAIS le
     * nom PRINCIPAL d'une fiche : une fiche garde le droit d'être trouvée sous son propre titre,
     * seul un alias supplémentaire est assez accessoire pour être sacrifié. Comparaison insensible
     * à la casse (voir isNeverAutoAlias()).
     */
    // 2026-08-29, MESURÉ : « dos » a posé 3 liens sur 900 pages de production, et les TROIS
    // sont faux (une vue « de dos » d'un personnage 3D, un « sac à dos transparent » dans une
    // fiche pour enfants, un skieur qui porte quelque chose « sur son dos »). Zéro lien vrai.
    // La sensibilité à la casse ne peut RIEN ici, contrairement à l'intuition : l'alias curé est
    // stocké en minuscule, donc case_sensitive le rendrait sensible à sa propre casse minuscule
    // et laisserait passer les trois mêmes cas.
    // Coût assumé : isNeverAutoAlias() compare en minuscule, donc ceci bloque AUSSI l'alias
    // dérivé « DoS ». Aucun « DoS » correct n'a été lié dans l'échantillon, et la fiche reste
    // atteignable par son nom principal et par la recherche interne.
    public const ALIAS_NEVER_AUTO = ['cnn', 'dos', 'requête', 'requêtes', 'témoin'];

    /**
     * 2026-08-29 : vrai si cette chaîne (alias curé, qualifier dérivé, ou variante morphologique)
     * figure dans ALIAS_NEVER_AUTO. Point de vérité unique pour les cinq endroits de loadTerms()
     * où une entrée de ce type entre dans $terms - jamais appelée sur le nom PRINCIPAL d'une fiche.
     */
    private static function isNeverAutoAlias(string $s): bool
    {
        return in_array(mb_strtolower(trim($s)), self::ALIAS_NEVER_AUTO, true);
    }

    /**
     * 2026-05-11 #138 : extrait aliases auto depuis un nom "X (Y)".
     *
     * Retourne :
     * - la base "X" (toujours utile : "Loi 25 (Québec)" → "Loi 25")
     * - le qualifier "Y" UNIQUEMENT si c'est un acronyme tout-majuscule (CNN, RNN, GAN, NAS, APE)
     *   ou un mot capitalisé ≤10 chars (ReAct), ET s'il ne nomme pas une organisation
     *   (cf. QUALIFIER_ORGANISATION). Évite "Québec", "mécanisme", phrases descriptives.
     *
     * @return array<int, string> liste d'aliases auto-dérivés (peut être vide)
     */
    public static function extractQualifierAliases(string $name): array
    {
        if (! preg_match('/^(.+?)\s*\(([^)]+)\)\s*$/u', $name, $m)) {
            return [];
        }
        $base = trim($m[1]);
        $qualifier = trim($m[2]);
        $out = [];

        if ($base !== '' && $base !== $name) {
            $out[] = $base;
        }
        // Un fabricant en qualifier désambiguïse le terme, il n'en est pas un synonyme.
        if (in_array(mb_strtolower($qualifier), self::QUALIFIER_ORGANISATION, true)) {
            return $out;
        }
        // Push qualifier seulement si acronyme propre (évite faux positifs)
        if ($qualifier !== '' && (
            preg_match('/^[A-Z]{2,8}$/u', $qualifier) ||           // CNN, RNN, GAN, NAS, APE
            preg_match('/^[A-Z][a-zA-Z]{1,9}$/u', $qualifier)      // ReAct, Adam
        )) {
            $out[] = $qualifier;
        }
        return $out;
    }

    /**
     * 2026-05-11 #146 Phase A : aliases morphologiques FR sans dépendance externe.
     *
     * Génère pluriel + capitalisations courantes pour un terme donné :
     * - pluriel FR : -s régulier, -aux pour -al, -eaux pour -eau, -eux pour -eu
     * - capitalisations : mb_strtolower + ucfirst si différents
     * - exclusions : acronymes tout-cap (CNN reste CNN), noms ≤3 chars (IA), expressions multi-mots déjà pluriels
     *
     * Couvre ~80% des variantes manquées sans LLM, coût récurrent zéro.
     * Note 95/100 cf benchmark sonar-pro 2026.
     *
     * @return array<int, string>
     */
    public static function extractMorphologicalAliases(string $name): array
    {
        $out = [];
        $clean = trim($name);
        if (mb_strlen($clean) < 4) return [];

        // Skip acronymes tout-cap (CNN, RNN, XAI, IoT) — pas de pluriel/casse à dériver
        if (preg_match('/^[A-Z0-9]{2,8}$/u', $clean)) return [];

        // Garde-fou #163 : un homographe à initiale MAJUSCULE (nom propre type « Transformer » dont la
        // forme minuscule « transformer » est un mot/verbe FR courant de STOP_LIST_FR) ne doit JAMAIS
        // dériver sa forme minuscule, sinon cette variante re-matche le verbe partout dans les textes.
        $firstChar = mb_substr($clean, 0, 1);
        $isUpperInitialHomograph = mb_strtolower($firstChar) !== $firstChar
            && in_array(mb_strtolower($clean), self::STOP_LIST_FR, true)
            && ! in_array(mb_strtolower($clean), self::HOMOGRAPH_LOWERCASE_OK, true);

        // Capitalisations
        $lower = mb_strtolower($clean);
        $titled = mb_convert_case($clean, MB_CASE_TITLE, 'UTF-8');
        $ucfirst = mb_strtoupper(mb_substr($clean, 0, 1)).mb_substr($lower, 1);
        foreach ([$lower, $titled, $ucfirst] as $v) {
            if ($isUpperInitialHomograph && $v === $lower) continue; // pas de forme minuscule pour un nom propre homographe
            if ($v !== $clean && ! in_array($v, $out, true)) $out[] = $v;
        }

        // Pluriel FR — uniquement si terme = 1 mot ou expr courte ≤3 mots
        $words = preg_split('/\s+/u', $clean);
        if (count($words) > 3) return $out;

        // Détecte si déjà pluriel (finit par 's' ou 'x' précédé voyelle)
        $endsPlural = (bool) preg_match('/(s|x)$/iu', $clean);

        if (! $endsPlural) {
            $plurals = [];
            // Règles FR de pluriel
            if (preg_match('/(eau|eu)$/iu', $clean)) {
                $plurals[] = $clean.'x';
            } elseif (preg_match('/al$/iu', $clean)) {
                $plurals[] = preg_replace('/al$/iu', 'aux', $clean);
            } elseif (preg_match('/(ail|au|ou)$/iu', $clean)) {
                // exceptions complexes — push -s safe + variante x
                $plurals[] = $clean.'s';
            } else {
                $plurals[] = $clean.'s';
            }
            foreach ($plurals as $p) {
                if ($p && $p !== $clean && ! in_array($p, $out, true)) {
                    $out[] = $p;
                    // Aussi version lowercase du pluriel — sauf pour un homographe à initiale majuscule (#163)
                    $pLower = mb_strtolower($p);
                    if (! $isUpperInitialHomograph && $pLower !== $p && ! in_array($pLower, $out, true)) $out[] = $pLower;
                }
            }
        }

        return $out;
    }

    /**
     * 2026-06-30 : résout l'URL cible pour un sigle potentiellement ambigu.
     * ACTION: calcul URL désambiguïsation acronymes homonymes
     * MCP: Hermes→qwen3-max | RAISON: 1 candidat → URL directe ; N candidats → page de désambiguïsation
     *
     * @param  string                          $acro       Sigle exact (ex. "ATE")
     * @param  array<int, array<string,mixed>> $candidates Tableau d'entrées candidats (chacune avec 'url')
     */
    public static function resolveAmbiguousAcronymUrl(string $acro, array $candidates): string
    {
        if (count($candidates) === 1) {
            return $candidates[0]['url'];
        }

        return '/acronymes-education/disambiguate/'.strtolower($acro);
    }

    /**
     * 2026-05-05 #145 WSD : auto-escalade strategy si terme dans STOP_LIST_FR (mots/verbes FR courants).
     * Fix #163 2026-06-17 : l'escalade visait case_sensitive dès l'origine (le docblock STOP_LIST le dit),
     * mais le code retournait partial_case_sensitive (1re lettre tolérante) ET ne se déclenchait que sur 'loose'.
     * Résultat : « Transformer » (stocké partial) surlignait le verbe minuscule « transformer ».
     * Désormais : un homographe à initiale MAJUSCULE (nom propre type « Transformer ») exige la casse stricte
     * (seule la forme capitalisée est liée, jamais le mot/verbe minuscule), même si déjà stocké en 'partial'.
     * Un homographe canonique en minuscule conserve l'escalade tolérante #150 depuis 'loose'.
     * Stratégies déjà strictes/neutralisées (case_sensitive, exact_phrase, never_auto) ne sont jamais modifiées.
     */
    protected static function escalateStrategyIfStopList(string $name, string $currentStrategy): string
    {
        if (in_array($currentStrategy, ['case_sensitive', 'exact_phrase', 'never_auto'], true)) {
            return $currentStrategy;
        }

        $lowered = mb_strtolower($name);
        if (! in_array($lowered, self::STOP_LIST_FR, true)) {
            return $currentStrategy;
        }

        $firstChar = mb_substr($name, 0, 1);
        $isUpperInitial = $firstChar !== '' && mb_strtolower($firstChar) !== $firstChar;
        // Casse stricte SAUF pour les emprunts dont la minuscule est le terme légitime (token, pipeline).
        if ($isUpperInitial && ! in_array($lowered, self::HOMOGRAPH_LOWERCASE_OK, true)) {
            return 'case_sensitive';
        }

        return $currentStrategy === 'loose' ? 'partial_case_sensitive' : $currentStrategy;
    }

    /**
     * 2026-05-05 #150 : construit pattern regex partial_case_sensitive.
     * Pour chaque mot du nom, la 1ère lettre devient [Aa] (tolérante), le reste reste strict.
     * Ex: 'Score Elo' → '[Ss]core [Ee]lo' qui matche 'Score Elo', 'score Elo', mais pas 'score elo' (E strict après normalisation pos 0 du mot).
     *
     * Wait: le match interne est strict mais la 1ère lettre du 2e mot ('E' de 'Elo') est aussi position 0 d'un mot.
     * Donc 'Elo' → '[Ee]lo' qui matche 'Elo' ET 'elo'. Le user voulait que Elo reste strict.
     * Solution affinée : seule la 1ère lettre du PREMIER mot devient tolérante, les autres mots strict.
     */
    protected static function buildPartialCasePattern(string $name): string
    {
        if (mb_strlen($name) === 0) return '';

        // 1ère lettre tolérante (case-insensitive sur le 1er char), reste du nom strict
        $firstChar = mb_substr($name, 0, 1);
        $rest = mb_substr($name, 1);
        $firstLower = mb_strtolower($firstChar);
        $firstUpper = mb_strtoupper($firstChar);

        // Si la 1ère lettre n'a pas de variation casse (chiffre, accent neutre), utilise tel quel
        if ($firstLower === $firstUpper) {
            return preg_quote($name, '/');
        }

        return '['.$firstLower.$firstUpper.']'.preg_quote($rest, '/');
    }

    /**
     * 2026-08-02 #1526 : epoch courant du cache de RÉSULTAT linkify() (voir linkify()/flushCache()).
     * Défaut 0 tant que flushCache() n'a jamais tourné depuis le déploiement de ce compteur.
     */
    protected static function currentTermsEpoch(): int
    {
        return (int) Cache::get(self::TERMS_EPOCH_KEY, 0);
    }

    /**
     * Invalidation cache (appelée par Model events sur Term + Acronym).
     */
    public static function flushCache(): void
    {
        // #158 flush toutes les versions cache (v2-v8) pour migration propre
        foreach (['fr_CA', 'fr', 'en', 'en_CA'] as $loc) {
            Cache::forget(self::CACHE_KEY.$loc);
            // 2026-08-29 : v12 ajoutée ici en même temps que le bump v13 (ALIAS_NEVER_AUTO) - même
            // raison que la note 2026-08-21 ci-dessous : sans elle, une clé v12 resterait servie
            // jusqu'à l'expiration de son TTL après un flush explicite. v11 profite du même
            // correctif : elle avait été omise lors du bump v11→v12 du 2026-08-28, écart repéré en
            // corrigeant celui-ci, comblé ici plutôt que laissé pour la prochaine fois.
            Cache::forget('glossary.terms.v12.'.$loc);
            Cache::forget('glossary.terms.v11.'.$loc);
            // 2026-08-21 : v9 (et v8, oubliée lors d'un bump précédent) ajoutées ici en même temps
            // que le bump v10 - sans quoi une clé de la version précédente resterait servie jusqu'à
            // l'expiration de son TTL après un flush explicite.
            Cache::forget('glossary.terms.v10.'.$loc);
            Cache::forget('glossary.terms.v9.'.$loc);
            Cache::forget('glossary.terms.v8.'.$loc);
            Cache::forget('glossary.terms.v7.'.$loc);
            Cache::forget('glossary.terms.v6.'.$loc);
            Cache::forget('glossary.terms.v5.'.$loc);
            Cache::forget('glossary.terms.v4.'.$loc);
            Cache::forget('glossary.terms.v3.'.$loc);
            Cache::forget('glossary.terms.v2.'.$loc);
        }

        // 2026-08-02 #1526 : incrémente l'epoch pour invalider tout résultat linkify() caché précédemment
        // (les clés de cache résultat incluent cet epoch, voir linkify()). Cache::increment() n'est pas
        // garanti atomique sur le driver 'file' (CACHE_STORE=file en prod) → lecture puis forever() explicite.
        Cache::forever(self::TERMS_EPOCH_KEY, self::currentTermsEpoch() + 1);
    }

    /**
     * 2026-05-07 #222 : strip markdown inline pour tooltips data-tooltip CSS.
     * Le tooltip CSS `content: attr(data-tooltip)` rend du texte brut, donc les **gras**,
     * *italiques*, `code` et liens [txt](url) doivent être déballés en texte clair.
     */
    protected static function stripMarkdownInline(string $text): string
    {
        $text = preg_replace('/!\[([^\]]*)\]\([^\)]+\)/u', '$1', $text); // images ![alt](url) -> alt
        $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/u', '$1', $text); // links [txt](url) -> txt
        $text = preg_replace('/\*\*\*(.+?)\*\*\*/u', '$1', $text); // bold+italic
        $text = preg_replace('/\*\*(.+?)\*\*/u', '$1', $text); // bold
        $text = preg_replace('/(?<!\w)\*(.+?)\*(?!\w)/u', '$1', $text); // italic *...*
        $text = preg_replace('/(?<!\w)_(.+?)_(?!\w)/u', '$1', $text); // italic _..._
        $text = preg_replace('/`+([^`]+)`+/u', '$1', $text); // inline code

        return $text;
    }

    /**
     * Walk récursif DOM + remplacement text nodes hors zones interdites.
     */
    protected static function walkAndReplace(\DOMDocument $dom, \DOMNode $node, array $terms, array &$seen, int &$linkCount, int $maxLinks, ?string $skipSlug, int $maxOcc = self::MAX_OCCURRENCES_PER_TERM, bool $perSection = false, int &$currentSection = 0): void
    {
        if ($linkCount >= $maxLinks) return;

        // Skip zones interdites. button/select/option/textarea : 2026-07-03 - un lien injecté dans le
        // texte d'un <button> (ex. "Générer mon prompt optimisé") intercepte le clic et navigue vers le
        // glossaire au lieu de soumettre le formulaire (incident générateur de prompt interactif article 16).
        $skipTags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a', 'code', 'pre', 'abbr', 'blockquote', 'dfn', 'label', 'script', 'style', 'kbd', 'samp', 'var', 'button', 'select', 'option', 'textarea'];
        if ($node->nodeType === XML_ELEMENT_NODE) {
            $tag = strtolower($node->nodeName);
            // 2026-07-25 #1350 : perSection=true -> un <h2> marque le début d'une nouvelle section,
            // incrémente le compteur AVANT le skip habituel (le h2 lui-même n'est jamais linkifié).
            if ($perSection && $tag === 'h2') {
                $currentSection++;
            }
            if (in_array($tag, $skipTags, true)) {
                return;
            }
        }

        // Cloner les enfants car on va modifier la structure pendant l'iteration
        $children = [];
        foreach ($node->childNodes as $c) $children[] = $c;

        foreach ($children as $child) {
            if ($linkCount >= $maxLinks) break;

            if ($child->nodeType === XML_TEXT_NODE) {
                $text = $child->nodeValue;
                if (! $text || mb_strlen(trim($text)) < self::MIN_LENGTH) continue;

                $replaced = self::matchInText($dom, $text, $terms, $seen, $linkCount, $maxLinks, $skipSlug, $maxOcc, $perSection, $currentSection);
                if ($replaced !== null) {
                    // Replace text node par fragment (mix text + <a>)
                    $parent = $child->parentNode;
                    foreach ($replaced as $newNode) {
                        $parent->insertBefore($newNode, $child);
                    }
                    $parent->removeChild($child);
                }
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                self::walkAndReplace($dom, $child, $terms, $seen, $linkCount, $maxLinks, $skipSlug, $maxOcc, $perSection, $currentSection);
            }
        }
    }

    /**
     * Cherche dans un text le premier match non-encore-vu, retourne fragment ou null.
     *
     * @return array<\DOMNode>|null
     */
    protected static function matchInText(\DOMDocument $dom, string $text, array $terms, array &$seen, int &$linkCount, int $maxLinks, ?string $skipSlug, int $maxOcc = self::MAX_OCCURRENCES_PER_TERM, bool $perSection = false, int $currentSection = 0): ?array
    {
        foreach ($terms as $term) {
            if ($linkCount >= $maxLinks) return null;
            // 2026-05-11 #158 : autorise jusqu'à MAX_OCCURRENCES_PER_TERM wraps du même terme par page
            $seenKey = $term['slug'].'|'.$term['type'];
            // 2026-07-25 #1350 : namespace la clé par section H2 si perSection actif -> 1 occurrence par
            // terme et par section au lieu de $maxOcc par article entier (moins agressant visuellement).
            if ($perSection) {
                $seenKey .= '|s'.$currentSection;
            }
            if (($seen[$seenKey] ?? 0) >= $maxOcc) continue;
            if ($skipSlug && $term['slug'] === $skipSlug) continue;

            $name = $term['name'];
            // 2026-05-05 #145 WSD : applique la match_strategy
            $strategy = $term['match_strategy'] ?? ($term['case_sensitive'] ?? false ? 'case_sensitive' : 'loose');

            // 2026-05-05 #150 : partial_case_sensitive = 1ère lettre de chaque mot tolérante, reste strict.
            // Ex: 'Score Elo' matche 'Score Elo' ET 'score Elo' MAIS pas 'score elo' (E majuscule strict).
            // ACTION : la frontiere de fin refuse aussi un point suivi d'un CHIFFRE, pour ne pas
            // couper un numero de version en deux. Trouve en production le 2026-08-26 : le terme
            // « Gemini 3 » matchait a l'interieur de « Gemini 3.5 Transcribe », rendant
            // `<a>Gemini 3</a>.5 Transcribe` avec l'infobulle d'un AUTRE modele. Un point n'etant
            // ni lettre ni chiffre, l'ancienne frontiere le laissait passer.
            // Une fin de phrase (« ... utilise Gemini 3. ») reste liee : le point n'y est pas
            // suivi d'un chiffre.
            // MCP: SELF (<5 lignes)
            // RAISON: correctif de frontiere sur le point unique ou le motif est construit.
            $finDeMot = '(?![\p{L}\p{N}_\-\/]|\.\w)';
            // 2026-08-28 : garde de FAUX COMPOSÉ (TOOL_COMPOUND_EXCLUSIONS, ex. « Composer » dans
            // « Paragraph Composer »). Lookbehind négatif À LARGEUR FIXE par préfixe exclu
            // (préfixe + 1 espace, casse insensible sur le seul préfixe) : rejette UNIQUEMENT le
            // composé précis, jamais le terme employé seul ailleurs dans le même texte ou la même
            // page - donc aucune récursion/boucle à changer dans matchInText()/walkAndReplace().
            $debutDeMot = '';
            foreach (($term['exclude_after'] ?? []) as $prefixExclu) {
                $debutDeMot .= '(?<!(?i:'.preg_quote($prefixExclu, '/').')[\s\x{00A0}])';
            }
            if ($strategy === 'partial_case_sensitive') {
                $pattern = '/(?<![\p{L}\p{N}._\-\/])'.$debutDeMot.self::buildPartialCasePattern($name).$finDeMot.'/u';
            } else {
                $pattern = '/(?<![\p{L}\p{N}._\-\/])'.$debutDeMot.preg_quote($name, '/').$finDeMot.'/u';
                if ($strategy === 'loose') $pattern .= 'i';
            }
            // case_sensitive ET exact_phrase : pas de flag i (casse exacte)

            if (! preg_match($pattern, $text, $m, PREG_OFFSET_CAPTURE)) continue;

            $matchedText = $m[0][0];
            $offset = $m[0][1];
            $before = substr($text, 0, $offset);
            $after = substr($text, $offset + strlen($matchedText));

            // Build <a> wrap
            $a = $dom->createElement('a');
            $a->appendChild($dom->createTextNode($matchedText));
            $a->setAttribute('href', $term['url']);
            $a->setAttribute('class', 'glossary-link');
            // 2026-05-05 #142 : data-tooltip lu par CSS pur, retire title= pour eviter double tooltip natif lent
            $a->setAttribute('data-tooltip', $term['definition']);
            $a->setAttribute('aria-label', $name.' – '.($term['type'] === 'acronym' || $term['type'] === 'acronym_full' ? 'voir acronyme (nouvel onglet)' : 'voir glossaire (nouvel onglet)'));
            // 2026-05-05 #141 : ouvre dans nouvel onglet pour preserver la lecture en cours
            $a->setAttribute('target', '_blank');
            $a->setAttribute('rel', 'noopener noreferrer');

            // 2026-05-11 #158 : counter cumulatif inter-appels (autorise jusqu'à 10×/terme/page)
            $seen[$seenKey] = ($seen[$seenKey] ?? 0) + 1;
            self::$matchedThisRequest[$term['slug']] = $term;
            $linkCount++;

            // 2026-05-11 #155+#153 : récursion BIDIRECTIONNELLE sur $before ET $after
            // pour wraper TOUS les termes dans le MÊME text-node. Critique pour
            // "Les GPU NVIDIA H100 et TPU Google..." où Google matchait en 1er
            // (sort longueur DESC) et $before "Les GPU... TPU " perdait GPU+TPU.
            $fragment = [];
            if ($before !== '') {
                $beforeFragment = self::matchInText($dom, $before, $terms, $seen, $linkCount, $maxLinks, $skipSlug, $maxOcc, $perSection, $currentSection);
                if ($beforeFragment !== null) {
                    $fragment = $beforeFragment;
                } else {
                    $fragment[] = $dom->createTextNode($before);
                }
            }
            $fragment[] = $a;
            if ($after !== '') {
                $afterFragment = self::matchInText($dom, $after, $terms, $seen, $linkCount, $maxLinks, $skipSlug, $maxOcc, $perSection, $currentSection);
                if ($afterFragment !== null) {
                    $fragment = array_merge($fragment, $afterFragment);
                } else {
                    $fragment[] = $dom->createTextNode($after);
                }
            }

            return $fragment;
        }

        return null;
    }
}
