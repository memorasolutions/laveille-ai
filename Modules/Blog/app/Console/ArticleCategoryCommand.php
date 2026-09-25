<?php

declare(strict_types=1);

namespace Modules\Blog\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;

/**
 * Porte d'écriture bornée pour poser la catégorie (category_id) d'un article de blogue -
 * jamais d'Eloquent/SQL direct par l'agent (même doctrine que Modules\Blog\Console\
 * ArticleVerifyCommand).
 *
 * Mesuré en production le 2026-09-25 : au moins 3 articles publiés (et probablement des
 * articles planifiés) n'ont aucune catégorie. Aucune commande n'existait pour la poser - les
 * articles récents sont créés par scripts ponctuels qui n'y pensent pas toujours.
 *
 * Deux formes, jamais combinées :
 *   - `blog:category --missing` : liste en JSON les articles sans category_id (brouillons
 *     compris, pour que rien n'échappe à l'audit).
 *   - `blog:category {article} {categorie} [--dry-run]` : pose la catégorie d'UN article.
 *     {article} accepte un id (entier) ou un slug (traduisible Spatie) - même logique de
 *     correspondance que Modules\Blog\Http\Controllers\PublicPostController::show() :
 *     colonne JSON de la locale courante EN PREMIER, colonne brute en repli. {categorie} est
 *     le slug de Modules\Blog\Models\Category, dont le slug est LUI AUSSI traduisible
 *     (`public array $translatable` du modèle) bien que la migration déclare la colonne comme
 *     un simple `string(120)` - Spatie\Translatable encode quand même en JSON dans cette
 *     colonne, donc la même logique de correspondance (JSON locale courante -> colonne brute)
 *     s'applique aux deux résolutions.
 *
 * DÉCISION - colonne texte `category` (legacy) JAMAIS écrite ici : vérifié dans le code
 * (Article::toSearchableArray() la lit pour Scout, ArticleFactory la remplit au hasard,
 * indépendamment de category_id) ET dans les données réelles (mesuré le 2026-09-25 : zéro
 * article, ni en local ni par lecture du modèle de données, n'a JAMAIS category_id et
 * `category` texte renseignés en même temps - les articles catégorisés via category_id ont
 * TOUS `category` texte à null). C'est donc une colonne morte relativement à la
 * catégorisation structurée : y écrire inventerait un couplage qui n'existe nulle part
 * ailleurs dans le projet. Si cette colonne redevient vivante un jour, corriger ici.
 *
 * DÉCISION - purge de cache : Article::booted() (Modules\Blog\Models\Article, déjà en place,
 * jamais modifié ici) vide déjà TOUT le cache de réponse Spatie via `static::saved(fn () =>
 * ResponseCache::clear())` sur CHAQUE sauvegarde, donc `$article->save()` ci-dessous déclenche
 * cette purge automatiquement, sans code additionnel. Un mécanisme plus CIBLÉ existe dans le
 * projet (App\Support\ResponseCache\PublicCachePurger::forgetRoutes()), mais il purge des
 * pages de LISTE par nom de route SANS paramètre (accueil, index de module) - il ne s'applique
 * pas à une page d'article paramétrée par slug, et n'est donc pas détourné de son usage prévu.
 *
 * DÉCISION - historique/rollback : Article utilise déjà Spatie\Activitylog\Traits\LogsActivity
 * avec `logOnlyDirty()` (voir Article::getActivitylogOptions()), donc `$article->save()`
 * journalise automatiquement l'ancienne ET la nouvelle valeur de category_id dans la table
 * `activity_log` - c'est le mécanisme d'historique existant du projet, jamais réinventé ici.
 * Modules\Blog\Observers\ArticleObserver::updated() y ajoute une seconde entrée descriptive.
 * En complément, cette commande affiche l'ancienne valeur en console AVANT d'écrire et
 * journalise l'opération dans le canal 'composition' (storage/logs/composition.log), même
 * convention qu'ArticleVerifyCommand.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class ArticleCategoryCommand extends Command
{
    protected $signature = 'blog:category
        {article? : id ou slug de l\'article de blogue}
        {categorie? : slug de la catégorie (Modules\\Blog\\Models\\Category)}
        {--missing : Liste en JSON les articles sans category_id, brouillons compris}
        {--dry-run : Simule sans écrire}';

    protected $description = 'Porte d\'écriture bornée pour poser la catégorie (category_id) d\'un article de blogue, ou lister ceux qui n\'en ont pas - jamais d\'Eloquent/SQL direct par l\'agent.';

    public function handle(): int
    {
        if ($this->option('missing')) {
            if ($this->argument('article') !== null || $this->argument('categorie') !== null) {
                $this->error('--missing ne prend aucun autre argument - utilise soit --missing seul, soit {article} {categorie}.');

                return self::FAILURE;
            }

            return $this->listMissing();
        }

        $articleArg = $this->argument('article');
        $categorieArg = $this->argument('categorie');

        if ($articleArg === null || $categorieArg === null) {
            $this->error('Fournis {article} et {categorie}, ou --missing seul pour lister les articles sans catégorie.');

            return self::FAILURE;
        }

        $article = $this->resolveArticle((string) $articleArg);

        if (! $article) {
            $this->error("Article de blogue introuvable (id ou slug) : {$articleArg}.");

            return self::FAILURE;
        }

        $category = $this->resolveCategory((string) $categorieArg);

        if (! $category) {
            $this->error("Catégorie introuvable (slug) : {$categorieArg}.");

            return self::FAILURE;
        }

        return $this->applyCategory($article, $category);
    }

    /**
     * `--missing` : brouillons compris (aucun scope `published()`/`draft()`), pour que rien
     * n'échappe à l'audit visé par le brief. Les articles à la corbeille (SoftDeletes) restent
     * exclus par le comportement par défaut d'Eloquent - un article supprimé n'a pas besoin
     * d'être catégorisé.
     */
    private function listMissing(): int
    {
        $locale = app()->getLocale();

        $articles = Article::query()
            ->whereNull('category_id')
            ->orderBy('id')
            ->get(['id', 'slug', 'status', 'published_at']);

        $payload = $articles->map(function (Article $article) use ($locale) {
            return [
                'id' => $article->id,
                'slug' => $this->displaySlug($article, $locale),
                'statut' => $article->getRawOriginal('status'),
                'published_at' => optional($article->published_at)->toDateTimeString(),
            ];
        })->values()->all();

        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    /**
     * Pose category_id, affiche et journalise l'ancienne valeur avant d'écrire. Idempotent :
     * si l'article porte déjà cette catégorie, aucune écriture n'est effectuée (succès direct).
     */
    private function applyCategory(Article $article, Category $category): int
    {
        $oldCategoryId = $article->getRawOriginal('category_id');
        $oldCategoryText = $article->getRawOriginal('category');
        $categorySlugDisplay = $this->displaySlug($category, app()->getLocale());

        $this->line("Article {$article->id} : category_id actuel = ".($oldCategoryId ?? 'null').", category (texte, non touchée) = ".($oldCategoryText ?? 'null').'.');

        if ($oldCategoryId !== null && (int) $oldCategoryId === $category->id) {
            $this->info("Article {$article->id} : déjà rattaché à la catégorie {$category->id} ({$categorySlugDisplay}) - idempotent, rien à écrire.");

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("[dry-run] Article {$article->id} : category_id passerait de ".($oldCategoryId ?? 'null')." à {$category->id} ({$categorySlugDisplay}). Aucune écriture effectuée.");

            return self::SUCCESS;
        }

        $article->category_id = $category->id;
        $article->save();

        Log::channel('composition')->info('blog:category - category_id posé', [
            'article_id' => $article->id,
            'old_category_id' => $oldCategoryId,
            'new_category_id' => $category->id,
            'category_slug' => $categorySlugDisplay,
        ]);

        $this->info("Article {$article->id} : category_id posé à {$category->id} ({$categorySlugDisplay}). Ancienne valeur : ".($oldCategoryId ?? 'null').'.');

        return self::SUCCESS;
    }

    /**
     * {article} = id (entier) en priorité, sinon slug - même logique de correspondance que
     * Modules\Blog\Http\Controllers\PublicPostController::show() : colonne JSON de la locale
     * courante d'abord, colonne brute en repli (compatibilité avec un slug jamais migré vers
     * une traduction, ou saisi tel quel).
     */
    private function resolveArticle(string $value): ?Article
    {
        if (ctype_digit($value)) {
            $article = Article::find((int) $value);

            if ($article) {
                return $article;
            }
        }

        $locale = app()->getLocale();

        return Article::query()
            ->where(function ($q) use ($value, $locale) {
                $q->where("slug->{$locale}", $value)
                    ->orWhere('slug', $value);
            })
            ->first();
    }

    /**
     * {categorie} = slug de Category. Le slug y est traduisible (voir docblock de classe) :
     * même repli JSON locale courante -> colonne brute qu'Article, sans scope `active()` - une
     * catégorie désactivée reste une catégorie valide à assigner (ce n'est pas le rôle de cette
     * commande de trancher sa visibilité publique).
     */
    private function resolveCategory(string $value): ?Category
    {
        $locale = app()->getLocale();

        return Category::query()
            ->where(function ($q) use ($value, $locale) {
                $q->where("slug->{$locale}", $value)
                    ->orWhere('slug', $value);
            })
            ->first();
    }

    /**
     * Slug affichable pour un modèle Spatie\Translatable : locale courante -> 'fr_CA' -> 1re
     * traduction disponible (même repli manuel qu'Article::getPublicUrl(), nécessaire tant que
     * config/translatable.php n'est pas publié dans ce projet).
     */
    private function displaySlug(Article|Category $model, string $locale): ?string
    {
        return $model->getTranslation('slug', $locale, false)
            ?: $model->getTranslation('slug', 'fr_CA', false)
            ?: collect($model->getTranslations('slug'))->first();
    }
}
