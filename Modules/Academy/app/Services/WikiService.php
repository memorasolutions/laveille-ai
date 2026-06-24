<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F19 - WIKI : SOURCE UNIQUE (DRY) de l'activité « wiki » (item de leçon « wiki », type
 * Moodle « Wiki » : pages collaboratives + historique). Lue côté SERVEUR par le contrôleur
 * d'actions, le lecteur (lesson.blade) et l'éditeur (CourseEditor). La configuration, la
 * liste des pages, l'historique, le slug unique, le snapshot de révision et le rendu des
 * liens inter-pages ne vivent qu'ICI.
 *
 * Le payload de l'item porte (aucune nouvelle colonne, comme quiz/choice/feedback/forum) :
 *   - intro              : texte d'introduction facultatif ;
 *   - allow_student_edit : les étudiants inscrits peuvent éditer les pages (défaut true).
 *                          false => seul un gérant édite ; les étudiants lisent.
 *
 * VERSIONING (snapshot avant édition) : à CHAQUE édition d'une page, on enregistre d'abord
 * l'état COURANT (title/body/revision/auteur) dans une révision, PUIS on écrit le nouveau
 * contenu et on incrémente le numéro de révision. L'historique est donc la suite des états
 * précédents, chacun attribué à son vrai auteur (edited_by de la page = auteur du courant).
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\WikiPage;
use Modules\Academy\Models\WikiRevision;

final class WikiService
{
    /** Longueur maximale du titre d'une page (anti-abus). */
    public const TITLE_MAX = 200;

    /** Longueur maximale du corps d'une page (anti-abus ; pages riches mais bornées). */
    public const BODY_MAX = 50000;

    /** Nom du champ honeypot anti-spam (caché, doit rester vide). */
    public const HONEYPOT = 'hp_url';

    /** Borne dure du nombre de pages chargées à l'affichage (anti-explosion). */
    public const MAX_PAGES = 500;

    /** Nombre de révisions par page d'historique. */
    public const REVISIONS_PER_PAGE = 20;

    // ─────────────────────────────────────────────────────────────────────────────
    // LECTURE DE LA CONFIGURATION (payload)
    // ─────────────────────────────────────────────────────────────────────────────

    public static function intro(LessonItem $item): string
    {
        $intro = is_array($item->payload ?? null) ? ($item->payload['intro'] ?? '') : '';

        return is_string($intro) ? $intro : '';
    }

    /** Les étudiants inscrits peuvent-ils éditer ? DÉFAUT true (clé absente = autorisé). */
    public static function allowsStudentEdit(LessonItem $item): bool
    {
        $raw = is_array($item->payload ?? null) ? ($item->payload['allow_student_edit'] ?? null) : null;

        return $raw === null ? true : (bool) $raw;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // PAGES (accueil d'abord, puis par titre) + résolution
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Pages NON supprimées de l'item, page d'accueil en tête puis par titre. Charge en
     * lot l'auteur d'origine et le dernier éditeur (anti N+1). Bornée à MAX_PAGES.
     *
     * @return Collection<int, WikiPage>
     */
    public static function pages(LessonItem $item): Collection
    {
        return WikiPage::forItem($item->id)
            ->with(['creator:id,name', 'editor:id,name'])
            ->orderByDesc('is_home')
            ->orderBy('title')
            ->limit(self::MAX_PAGES)
            ->get();
    }

    /** Page d'accueil de l'item (is_home), ou la première par défaut, ou null si vide. */
    public static function homePage(LessonItem $item): ?WikiPage
    {
        return WikiPage::forItem($item->id)->orderByDesc('is_home')->orderBy('id')->first();
    }

    /** Révisions d'une page, de la plus récente à la plus ancienne (paginées). */
    public static function revisions(WikiPage $page): LengthAwarePaginator
    {
        return WikiRevision::where('wiki_page_id', $page->id)
            ->with('user:id,name')
            ->orderByDesc('revision')
            ->paginate(self::REVISIONS_PER_PAGE, ['*'], 'wikirev'.$page->id.'page');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // ÉCRITURE : création, édition (snapshot), accueil par défaut
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Garantit qu'une page d'accueil existe pour le wiki (idempotent). Appelée à la
     * création de l'item « wiki » dans l'éditeur. La 1re page est marquée is_home.
     */
    public static function ensureHomePage(LessonItem $item, ?int $userId = null): WikiPage
    {
        $existing = WikiPage::forItem($item->id)->orderByDesc('is_home')->orderBy('id')->first();
        if ($existing) {
            return $existing;
        }

        return WikiPage::create([
            'lesson_item_id' => $item->id,
            'title'          => 'Accueil',
            'slug'           => 'accueil',
            'body'           => '',
            'created_by'     => $userId,
            'edited_by'      => $userId,
            'revision'       => 1,
            'is_home'        => true,
            'is_locked'      => false,
        ]);
    }

    /**
     * Crée une page. La TOUTE PREMIÈRE page d'un wiki devient la page d'accueil.
     * Le slug est unique par wiki (les pages soft-supprimées comptent pour éviter les
     * collisions d'URL au moment d'une restauration éventuelle).
     */
    public static function createPage(LessonItem $item, ?int $userId, string $title, string $body): WikiPage
    {
        $isFirst = ! WikiPage::withTrashed()->forItem($item->id)->exists();

        return WikiPage::create([
            'lesson_item_id' => $item->id,
            'title'          => $title,
            'slug'           => self::uniqueSlug($item, $title),
            'body'           => $body,
            'created_by'     => $userId,
            'edited_by'      => $userId,
            'revision'       => 1,
            'is_home'        => $isFirst,
            'is_locked'      => false,
        ]);
    }

    /**
     * Applique une édition à une page. VERSIONING : snapshot de l'état COURANT (attribué à
     * son auteur edited_by) AVANT d'écrire le nouveau contenu, puis incrément du numéro de
     * révision et mise à jour de l'éditeur courant. Le slug reste STABLE (identité d'URL).
     * La restauration d'une révision réutilise ce chemin (le contenu vient de la révision).
     */
    public static function applyEdit(WikiPage $page, ?int $userId, string $title, string $body): void
    {
        WikiRevision::create([
            'wiki_page_id' => $page->id,
            'user_id'      => $page->edited_by,
            'title'        => $page->title,
            'body'         => $page->body,
            'revision'     => $page->revision,
            'snapshot_at'  => now(),
        ]);

        $page->update([
            'title'     => $title,
            'body'      => $body,
            'edited_by' => $userId,
            'revision'  => $page->revision + 1,
        ]);
    }

    /**
     * Slug unique pour le wiki. Dérivé du titre ; suffixé -2, -3… en cas de collision
     * (pages soft-supprimées incluses). $ignoreId exclut la page en cours (édition).
     */
    public static function uniqueSlug(LessonItem $item, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $base = $base !== '' ? Str::limit($base, 200, '') : 'page';

        $slug = $base;
        $i    = 2;
        while (
            WikiPage::withTrashed()
                ->forItem($item->id)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // RENDU : corps markdown sûr + liens inter-pages [[Titre]]
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Rend le corps d'une page en HTML SÛR (markdown html_input=strip => anti-XSS), puis
     * transforme les liens internes [[Titre]] en lien vers la page existante du même wiki
     * (paramètre de requête sur la leçon courante), ou en repère « page inexistante ».
     *
     * @param  Collection<int, WikiPage>|null  $pages  liste déjà chargée (anti N+1) ; sinon rechargée.
     */
    public static function renderBody(LessonItem $item, WikiPage $page, ?Collection $pages = null): string
    {
        $html  = LessonItem::renderRichText($page->body);
        $pages = $pages ?? self::pages($item);

        $bySlugTitle = [];
        foreach ($pages as $p) {
            $bySlugTitle[mb_strtolower($p->title)] = $p->slug;
        }

        $rendered = preg_replace_callback(
            '/\[\[([^\[\]\r\n]{1,200})\]\]/u',
            function (array $m) use ($item, $bySlugTitle): string {
                $title = trim($m[1]);
                $label = e($title);
                $key   = mb_strtolower($title);

                if (isset($bySlugTitle[$key])) {
                    $href = '?wpage_'.$item->id.'='.rawurlencode($bySlugTitle[$key]).'#item-'.$item->id;

                    return '<a class="academy-wiki-link" href="'.e($href).'">'.$label.'</a>';
                }

                return '<span class="academy-wiki-missing" title="Page inexistante">'.$label.'</span>';
            },
            $html
        );

        return is_string($rendered) ? $rendered : $html;
    }
}
