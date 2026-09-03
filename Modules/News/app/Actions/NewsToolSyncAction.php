<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION: synchronisation et suggestion d'outils liés à une actualité (DRY).
 * Factorisé depuis AdminNewsController pour être partagé avec ArticleToolsEditor (Livewire).
 *
 * RAISON: éviter la duplication de la logique sync/suggest entre le contrôleur admin
 *         et le composant Livewire d'édition inline front-end.
 */

declare(strict_types=1);

namespace Modules\News\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\GlossaryLinkifier;
use Modules\Directory\Models\Tool;
use Modules\News\Models\NewsArticle;

final class NewsToolSyncAction
{
    /**
     * Synchronise les outils liés à l'actualité (source=manual).
     * Valide les IDs contre la table des outils publiés avant d'écrire.
     *
     * @param  array<int, int>  $toolIds
     */
    public function sync(NewsArticle $article, array $toolIds): void
    {
        $validIds = Tool::published()
            ->whereIn('id', $toolIds)
            ->pluck('id')
            ->all();

        $article->tools()->sync(
            array_fill_keys($validIds, ['source' => 'manual'])
        );
    }

    /**
     * Attache automatiquement les outils détectés qui ne sont PAS déjà liés à l'actualité
     * (source=auto). Contrairement à sync(), c'est un ajout PUR : ne touche JAMAIS aux
     * liaisons déjà existantes (qu'elles soient manual ou auto) - aucune sélection admin
     * n'est donc jamais écrasée par la détection automatique.
     *
     * @param  Collection<int, int>  $toolIds
     * @return int nombre d'outils effectivement attachés
     */
    public function attachAuto(NewsArticle $article, Collection $toolIds): int
    {
        $existingIds = $article->tools()
            ->pluck('directory_tools.id')
            ->map(fn ($id) => (int) $id);

        $newIds = $toolIds->map(fn ($id) => (int) $id)->diff($existingIds)->values();

        if ($newIds->isEmpty()) {
            return 0;
        }

        $article->tools()->attach(
            $newIds->mapWithKeys(fn (int $id) => [$id => ['source' => 'auto']])->all()
        );

        return $newIds->count();
    }

    /**
     * ACTION : intégration « Outils liés », promue depuis
     * Modules\News\Console\NewsApplyCommand::attachRelatedTools() (design doc 2026-09-03,
     * section 2.4) - résout les slugs (traduisibles Spatie) contre les outils PUBLIÉS de
     * l'annuaire et les attache en ajout PUR (source=auto, attachAuto() ne touche jamais une
     * liaison existante, manuelle ou automatique). Retourne des DONNÉES plutôt que d'imprimer
     * sur une sortie console : chaque appelant (CLI, futur écran admin) traduit dans son propre
     * format - même patron que CompositionPayloadNormalizer.
     *
     * `attached_count` (nombre RÉELLEMENT nouvellement attaché par attachAuto()) et
     * `attached_names` (TOUS les noms résolus, qu'ils aient été nouvellement attachés ou déjà
     * liés) sont volontairement DEUX champs distincts, jamais un seul recalculé depuis l'autre :
     * c'est exactement la paire utilisée par le message console d'origine
     * ("{$attached} outil(s) lié(s) (noms...)"), reproduite ici au mot près.
     *
     * @param  array<int, string>  $slugs
     * @return array{attached_count: int, attached_names: array<int, string>, unknown: array<int, string>, module_disabled: bool}
     */
    public function attachBySlug(NewsArticle $article, array $slugs): array
    {
        if (! class_exists(\Modules\Directory\Models\Tool::class)) {
            return ['attached_count' => 0, 'attached_names' => [], 'unknown' => [], 'module_disabled' => true];
        }

        $tools = Tool::published()->get(['id', 'slug', 'name']);
        $resolvedIds = [];
        $resolvedNames = [];
        $unknownSlugs = [];

        foreach ($slugs as $slug) {
            $match = $tools->first(fn ($tool) => in_array($slug, $tool->getTranslations('slug'), true));
            if ($match === null) {
                $unknownSlugs[] = $slug;

                continue;
            }
            $resolvedIds[] = (int) $match->id;
            $resolvedNames[] = $match->getTranslation('name', 'fr_CA', false)
                ?: $match->getTranslation('name', 'en', false)
                ?: $slug;
        }

        $attachedCount = 0;
        if ($resolvedIds !== []) {
            $attachedCount = $this->attachAuto($article, collect($resolvedIds));
            self::invalidatePublicCache($article);
        }

        return [
            'attached_count' => $attachedCount,
            'attached_names' => $resolvedNames,
            'unknown' => $unknownSlugs,
            'module_disabled' => false,
        ];
    }

    /**
     * ACTION : défaut 3 (2026-08-28), promue depuis
     * Modules\News\Console\NewsApplyCommand::detachRelatedTools() (design doc 2026-09-03,
     * section 2.4) - détache les outils visés par $slugs, et EUX SEULS - jamais un remplacement
     * de la liste complète, une omission ne doit JAMAIS pouvoir supprimer un lien. Résout les
     * slugs contre TOUS les outils de l'annuaire (pas seulement les PUBLIÉS, contrairement à
     * attachBySlug() ci-dessus) : un outil attaché à tort puis dépublié doit rester détachable,
     * sans quoi ce mécanisme recréerait le même piège qu'il referme. Retourne des DONNÉES
     * plutôt que d'imprimer sur une sortie console - même patron que attachBySlug() ci-dessus.
     *
     * @param  array<int, string>  $slugs
     * @return array{detached_count: int, detached_names: array<int, string>, unknown: array<int, string>, not_attached: array<int, string>, module_disabled: bool}
     */
    public function detachBySlug(NewsArticle $article, array $slugs): array
    {
        if (! class_exists(\Modules\Directory\Models\Tool::class)) {
            return ['detached_count' => 0, 'detached_names' => [], 'unknown' => [], 'not_attached' => [], 'module_disabled' => true];
        }

        $tools = Tool::query()->get(['id', 'slug', 'name']);
        $resolvedIds = [];
        $resolvedNames = [];
        $unknownSlugs = [];

        foreach ($slugs as $slug) {
            $match = $tools->first(fn ($tool) => in_array($slug, $tool->getTranslations('slug'), true));
            if ($match === null) {
                $unknownSlugs[] = $slug;

                continue;
            }
            $resolvedIds[] = (int) $match->id;
            $resolvedNames[$match->id] = $match->getTranslation('name', 'fr_CA', false)
                ?: $match->getTranslation('name', 'en', false)
                ?: $slug;
        }

        if ($resolvedIds === []) {
            return ['detached_count' => 0, 'detached_names' => [], 'unknown' => $unknownSlugs, 'not_attached' => [], 'module_disabled' => false];
        }

        // Un slug demandé mais non attaché est signalé à part (jamais confondu avec un slug
        // détaché) - detach() sur un pivot absent est un no-op silencieux côté Eloquent, donc le
        // signal doit être posé AVANT, en comparant aux liaisons réellement existantes.
        $attachedIds = $article->tools()->whereIn('directory_tools.id', $resolvedIds)->pluck('directory_tools.id')->map(fn ($id) => (int) $id)->all();
        $notAttachedIds = array_diff($resolvedIds, $attachedIds);
        $notAttachedNames = array_values(array_intersect_key($resolvedNames, array_flip($notAttachedIds)));

        if ($attachedIds === []) {
            return ['detached_count' => 0, 'detached_names' => [], 'unknown' => $unknownSlugs, 'not_attached' => $notAttachedNames, 'module_disabled' => false];
        }

        $article->tools()->detach($attachedIds);
        self::invalidatePublicCache($article);
        $detachedNames = array_values(array_intersect_key($resolvedNames, array_flip($attachedIds)));

        return [
            'detached_count' => count($attachedIds),
            'detached_names' => $detachedNames,
            'unknown' => $unknownSlugs,
            'not_attached' => $notAttachedNames,
            'module_disabled' => false,
        ];
    }

    /**
     * Invalidation ciblée du cache de la page publique de l'actualité (visiteurs anonymes),
     * après un changement du pivot outils. Extrait du pattern déjà utilisé par
     * ArticleToolsEditor::persist() (Livewire) pour rester réutilisable (jobs, futurs
     * appelants) sans dupliquer la logique Spatie ResponseCache.
     */
    public static function invalidatePublicCache(NewsArticle $article): void
    {
        if (! class_exists(\Spatie\ResponseCache\Facades\ResponseCache::class)) {
            return;
        }

        // On rompt la chaîne car usingSuffix() retourne AbstractRequestBuilder (lib Spatie),
        // ce qui tromperait PHPStan si chaîné avec forget() de CacheItemSelector.
        $cacheSelector = \Spatie\ResponseCache\Facades\ResponseCache::selectCachedItems()
            ->forUrls(route('news.show', $article->slug));
        $cacheSelector->usingSuffix('');
        $cacheSelector->forget();
    }

    /**
     * Suggère les outils détectés automatiquement dans le contenu de l'actualité.
     *
     * 2026-07-04 : le texte scanné inclut désormais aussi le résumé structuré IA
     * (hook + points clés + pourquoi important), pas seulement title/description/summary.
     * Pour beaucoup d'actualités, description/summary sont vides et TOUT le contenu réel
     * vit dans structured_summary (cf. show.blade.php : le résumé brut ne s'affiche QUE en
     * l'absence de résumé structuré) - sans cet ajout, la détection échouait silencieusement
     * à 0 dès que le nom de l'outil n'apparaissait que dans le résumé IA.
     *
     * Les outils de TOOL_NEVER_AUTO (mots aussi courants en français : "Claude", "Avec",
     * "Tome", "Make"...) sont exclus des $terms de GlossaryLinkifier et donc jamais détectés
     * par linkify() ci-dessous, quel que soit $text. On les détecte séparément sur $text
     * lui-même (titre affiché + corps affiché, cf. note du 2026-08-31 plus bas), mais en CASSE
     * STRICTE (la forme capitalisée "Claude", pas "claude") : un mot français courant comme
     * "avec" apparaît presque toujours en minuscule en milieu de phrase, jamais capitalisé
     * hors début de phrase - la casse stricte limite donc fortement le risque de faux positif
     * (confirmé : un outil "Avec" existe bel et bien, publié, dans l'annuaire).
     *
     * 2026-08-28 : la casse stricte seule ne suffit PAS pour les noms de
     * GlossaryLinkifier::TOOL_NEVER_RECAPTURE (sous-ensemble de TOOL_NEVER_AUTO) - leur
     * majuscule initiale ne distingue jamais l'outil du mot commun, y compris en tête de
     * phrase ou de titre ("Local AI", "Montage vidéo par lots", "Global AI Pulse" de KPMG,
     * "Thrive Logic" ont tous recapturé à tort l'outil homonyme - 4 faux liens mesurés sur un
     * backfill de 33). Ces noms sont donc exclus du mécanisme de recapture ci-dessous, sans
     * exception - voir le docblock de GlossaryLinkifier::TOOL_NEVER_RECAPTURE pour le détail.
     *
     * 2026-08-28 (« Composer » dans « Paragraph Composer », LibreOffice 26.8) : un TROISIÈME cas
     * ne se règle NI par TOOL_NEVER_AUTO NI par TOOL_NEVER_RECAPTURE - un nom dont la mention
     * SEULE est légitime (donc jamais bloqué ici), mais qui forme un faux composé avec un mot
     * précis accolé devant. Contrairement aux deux listes ci-dessus (blocage total, ce fichier),
     * ce cas se règle en AMONT, dans GlossaryLinkifier::TOOL_COMPOUND_EXCLUSIONS - un lookbehind
     * négatif sur le préfixe fautif, appliqué DANS le pattern de matchInText(). Comme suggest()
     * lit le résultat de CE MÊME appel linkify() (getLastMatchedTerms() ci-dessous), le correctif
     * couvre les deux mécanismes (corps de texte + attachement auto) sans code additionnel ici.
     *
     * 2026-08-31 (mandat #2091, « Clark » détecté dans « Clark Wiethorn », « Ghost » dans
     * « Ghost Murmur ») : le symétrique du cas ci-dessus - un mot qui SUIT le nom, cette fois -
     * touche AUSSI le mécanisme de recapture ci-dessous ($neverAutoIds), qui a son PROPRE
     * balayage regex (`\bNom\b` sur le texte brut), distinct de matchInText(). Un nom de
     * TOOL_NEVER_AUTO qui n'est PAS dans TOOL_NEVER_RECAPTURE (donc recapturable) pourrait subir
     * le même défaut : rien ne garantissait qu'il ne préfixe jamais un nom propre sans rapport.
     * Plutôt que dupliquer une seconde fois la définition de « qu'est-ce qu'une continuation sûre
     * après un nom d'outil », le filtre ci-dessous appelle GlossaryLinkifier::buildToolSuffixGuard()
     * - LE MÊME fragment regex que matchInText(), un seul endroit pour la même règle, comme
     * demandé par la revue du mandat #2091 : « une seule fonction de frontière, éprouvée par un
     * jeu de tests commun ».
     *
     * 2026-08-31 (décision mesurée sur 350 fiches tirées au hasard, 217 liens exploitables) :
     * $text ne contient plus le titre BRUT de la source, mais le texte RÉELLEMENT affiché au
     * lecteur - le titre optimisé (seo_title, à défaut title, exactement le même repli que
     * show.blade.php) et le corps affiché (structuredBodyText(), source unique de vérité déjà
     * utilisée pour le JSON-LD et le temps de lecture). Un lecteur qui voit un outil suggéré
     * trouve désormais toujours sa justification dans le texte sous ses yeux. Mesure : 0,5 %
     * de perte sur les liens exploitables (1/217), ZÉRO perte sur les vraies mentions d'outils
     * (0/74), aucun gain inverse (0/160) - restreindre au texte affiché ne fait rater aucun
     * outil réel. Le passif des liens déjà posés AVANT ce changement est un chantier distinct
     * (#2114, hors périmètre ici) - il dépend de cette décision et ne peut être mesuré avant.
     *
     * Renvoie une Collection d'IDs d'outils (sans enregistrer - l'admin valide).
     *
     * @return Collection<int, int>
     */
    public function suggest(NewsArticle $article): Collection
    {
        GlossaryLinkifier::resetState();

        // ACTION : ne chercher QUE dans le texte RÉELLEMENT affiché au lecteur (décision
        // mesurée du 2026-08-31 - voir docblock ci-dessus) - jamais dans le titre BRUT
        // de la source ni dans description (déjà exclue depuis le design doc "Actus - zéro
        // copie du texte source", 2026-08-13, section 4.1). Le titre suit EXACTEMENT le même
        // repli que show.blade.php (seo_title ?? title) : un repli différent introduirait un
        // nouvel écart entre ce qui est cherché et ce qui est vu. Le corps réutilise
        // structuredBodyText() - source unique de vérité du corps affiché, déjà consommée par
        // le JSON-LD et le temps de lecture - plutôt que de reconstruire ici la cascade
        // résumé structuré aplati/summary : si cette cascade change un jour, ce mécanisme suit
        // sans code additionnel.
        // MCP: SELF (<5 lignes)
        $displayedTitle = $article->seo_title ?? $article->title;

        $text = implode(' ', array_filter([
            strip_tags((string) $displayedTitle),
            strip_tags($article->structuredBodyText()),
        ]));

        GlossaryLinkifier::linkify($text);

        $matchedTerms = collect(GlossaryLinkifier::getLastMatchedTerms());
        $matchedTools = $matchedTerms->filter(fn (array $t) => ($t['type'] ?? '') === 'tool');

        // 2026-07-04 : JSON_UNQUOTE indispensable sous MySQL (règle projet permanente, cf. #227/#306) -
        // sans lui, JSON_EXTRACT renvoie la valeur JSON-quotée (ex. "claude" avec guillemets littéraux),
        // qui ne matche donc JAMAIS mb_strtolower($name) ('claude' sans guillemets). SQLite (tests)
        // déquote déjà les scalaires nativement dans JSON_EXTRACT et n'a PAS de fonction JSON_UNQUOTE
        // (cf. migration 2026_05_05_180100_set_transformer_case_sensitive.php) - d'où le conditionnel.
        $nameJsonExpr = DB::getDriverName() === 'mysql'
            ? "LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"fr_CA\"')))"
            : "LOWER(JSON_EXTRACT(name, '$.\"fr_CA\"'))";

        $neverAutoIds = collect(GlossaryLinkifier::TOOL_NEVER_AUTO)
            // 2026-08-28 : un nom de TOOL_NEVER_RECAPTURE n'est JAMAIS recapturé, même en
            // majuscule - c'est la faille fermée ici (voir docblock plus haut). Le reste de
            // TOOL_NEVER_AUTO ("avec", "tome", "make"...) garde le comportement d'origine.
            ->reject(fn (string $name) => in_array($name, GlossaryLinkifier::TOOL_NEVER_RECAPTURE, true))
            ->filter(fn (string $name) => (bool) preg_match('/\b'.preg_quote(ucfirst($name), '/').'\b'.GlossaryLinkifier::buildToolSuffixGuard().'/u', $text))
            ->map(fn (string $name) => Tool::published()
                ->whereRaw("{$nameJsonExpr} = ?", [mb_strtolower($name)])
                ->value('id'))
            ->filter()
            ->values();

        $locale = app()->getLocale();
        $slugsFromLinkifier = $matchedTools->pluck('slug');

        $detectedBySlug = Tool::published()
            ->whereIn("slug->{$locale}", $slugsFromLinkifier)
            ->pluck('id');

        // 2026-08-27 : GlossaryLinkifier donne la PRIORITÉ au glossaire/aux acronymes sur un
        // outil homonyme (voir loadTerms(), doc 2026-06-17 #164) - un nom déjà "pris" par une
        // fiche de glossaire n'est jamais ajouté à $terms avec type='tool', donc jamais retenu
        // par $matchedTools ci-dessus. Cette priorité est justifiée pour l'auto-lien du corps de
        // texte (une seule cible, jamais deux liens concurrents pour le lecteur) mais n'a aucune
        // raison de s'appliquer ici : la suggestion d'outils liés ne pose aucun lien, elle propose
        // un ID que l'admin valide. On reprend donc les termes détectés qui NE sont PAS de type
        // 'tool' (glossaire, acronyme) et on les confronte au nom exact des outils publiés - sans
        // dupliquer la détection (regex, désambiguïsation de casse) qui reste entièrement dans
        // GlossaryLinkifier. Mesuré en production le 2026-08-27 : 17 entités existent à la fois
        // dans le glossaire et l'annuaire (ChatGPT, Midjourney, Perplexity...), masquant 317
        // fiches publiées vivantes sans outil lié.
        $namesMaskedByGlossary = $matchedTerms
            ->reject(fn (array $t) => ($t['type'] ?? '') === 'tool')
            ->pluck('name')
            ->filter()
            ->unique()
            ->values();

        $detectedByName = $namesMaskedByGlossary->isEmpty()
            ? collect()
            : Tool::published()->whereIn("name->{$locale}", $namesMaskedByGlossary->all())->pluck('id');

        return $detectedBySlug->merge($detectedByName)->merge($neverAutoIds)->unique()->values();
    }
}
