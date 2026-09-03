<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;
use Modules\News\Http\Controllers\Admin\ConcentreBuilderController;
use Modules\News\Http\Controllers\Admin\NewsCompositionController;
use Modules\News\Http\Controllers\Admin\VideoGoalBuilderController;
use Modules\News\Http\Controllers\AdminNewsController;
use Modules\News\Http\Controllers\NewsSitemapController;
use Modules\News\Http\Controllers\PublicNewsController;

// ── Sitemap Google News dédié (R6 v1.6.0, best practice mai 2026) ──
Route::get('/news-sitemap.xml', [NewsSitemapController::class, 'index'])->name('news.sitemap');

// ── Routes publiques ──
Route::middleware('web')->group(function () {
    Route::get('/actualites', [PublicNewsController::class, 'index'])->name('news.index')->middleware('cacheResponse:600');

    // Module « vérification » (2026-08-21) : adresse stable et citable pour les fiches qui
    // vérifient une affirmation circulant ailleurs. Déclarée AVANT /actualites/{slug} n'est pas
    // nécessaire ici (préfixe différent), mais elle réutilise le même contrôleur et le même
    // rendu - elle ne fait qu'activer un filtre, jamais dupliquer l'index.
    Route::get('/verifications', [PublicNewsController::class, 'verifications'])->name('news.verifications')->middleware('cacheResponse:600');

    // Redirect 301 : anciennes URLs /actualites/{id} → /actualites/{slug}
    Route::get('/actualites/{id}', function (string $id) {
        $article = \Modules\News\Models\NewsArticle::findOrFail((int) $id);

        return redirect()->route('news.show', $article, 301);
    })->where('id', '[0-9]+');

    // P17 #235 — wrapper smart : si slug existe affiche fiche, sinon 301 vers /actualites
    Route::get('/actualites/{slug}', function (string $slug) {
        $article = \Modules\News\Models\NewsArticle::where('slug', $slug)->first();
        if (! $article) {
            return redirect('/actualites', 301);
        }

        return app(\Modules\News\Http\Controllers\PublicNewsController::class)->show($article);
    })->where('slug', '[a-z0-9\-]+')->name('news.show')->middleware('cacheResponse:600');
});

// ── Routes admin ──
Route::prefix('admin/news')
    ->name('admin.news.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        Route::get('sources', [AdminNewsController::class, 'index'])->name('sources.index');
        Route::get('sources/create', [AdminNewsController::class, 'create'])->name('sources.create');
        Route::post('sources', [AdminNewsController::class, 'store'])->name('sources.store');
        Route::get('sources/{source}/edit', [AdminNewsController::class, 'edit'])->name('sources.edit');
        Route::put('sources/{source}', [AdminNewsController::class, 'update'])->name('sources.update');
        Route::patch('sources/{source}/toggle', [AdminNewsController::class, 'toggleActive'])->name('sources.toggle');
        Route::post('sources/{source}/fetch', [AdminNewsController::class, 'fetchNow'])->name('sources.fetch');
        Route::delete('sources/{source}', [AdminNewsController::class, 'destroy'])->name('sources.destroy');

        // Articles
        Route::get('articles', [AdminNewsController::class, 'articles'])->name('articles.index');
        Route::get('articles/{article}/edit', [AdminNewsController::class, 'editArticle'])->name('articles.edit');
        Route::put('articles/{article}', [AdminNewsController::class, 'updateArticle'])->name('articles.update');
        Route::post('articles/{article}/rescore', [AdminNewsController::class, 'rescoreArticle'])->name('articles.rescore');
        Route::patch('articles/{article}/toggle', [AdminNewsController::class, 'toggleArticle'])->name('articles.toggle');
        Route::delete('articles/{article}', [AdminNewsController::class, 'destroyArticle'])->name('articles.destroy');
        Route::post('articles/{article}/upload-image', [AdminNewsController::class, 'uploadArticleImage'])->name('articles.upload-image');
        Route::post('articles/{article}/suggest-tools', [AdminNewsController::class, 'suggestTools'])->name('articles.suggest-tools');
        Route::post('articles/{article}/marquer-partage/{platform}', [AdminNewsController::class, 'markShared'])
            ->where('platform', 'linkedin|facebook')
            ->name('articles.mark-shared');
    });

// ── Concentre Builder (admin, S90) ──
Route::prefix('admin/concentre-builder')
    ->name('admin.concentre.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        Route::get('/', [ConcentreBuilderController::class, 'index'])->name('index');
        Route::get('/news', [ConcentreBuilderController::class, 'newsForWeek'])->name('news');
        Route::post('/generate', [ConcentreBuilderController::class, 'generate'])->name('generate');
        Route::post('/upload-image', [ConcentreBuilderController::class, 'uploadImage'])->name('upload-image')->middleware('throttle:10,1');
        Route::get('/runs/{id}', [ConcentreBuilderController::class, 'showRun'])->name('runs.show');
    });

// ── Écran de composition manuelle (admin, Phase A - design doc 2026-08-15) ──
// Mêmes droits que le Concentré (EnsureIsAdmin = permission 'view_admin_panel', pas de
// restriction superadmin) : c'est un outil éditorial courant, pas une fonctionnalité sensible
// réservée comme le générateur d'objectif vidéo ci-dessous.
Route::prefix('admin/news/composition')
    ->name('admin.news.composition.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        Route::get('/', [NewsCompositionController::class, 'index'])->name('index');
        Route::get('/candidates', [NewsCompositionController::class, 'candidates'])->name('candidates');
        // ── Améliorations en attente (2026-08-17), point 1 - « Créer une fiche depuis un lien » ──
        Route::post('/create-draft', [NewsCompositionController::class, 'createDraft'])->name('create-draft');
        Route::get('/{article}', [NewsCompositionController::class, 'show'])->name('show');
        Route::put('/{article}', [NewsCompositionController::class, 'update'])->name('update');
        Route::delete('/{article}/source-text', [NewsCompositionController::class, 'destroySourceText'])->name('destroy-source-text');
        // ── Récupération automatique Markdown + Publier-et-purger (design doc 2026-08-15,
        // révision 2026-08-17) ──
        Route::post('/{article}/fetch-source', [NewsCompositionController::class, 'fetchSource'])->name('fetch-source');
        Route::post('/{article}/publish', [NewsCompositionController::class, 'publish'])->name('publish');
        // 2026-08-21 : geste humain qui pose la signature éditoriale sur une fiche DÉJÀ publiée
        // (celles composées et publiées par l'agent n'en ont aucune, par construction). Même
        // groupe, mêmes protections d'accès que les autres actions de composition.
        Route::post('/{article}/marquer-relu', [NewsCompositionController::class, 'markReviewed'])->name('mark-reviewed');
        // ── Phase B (design doc 2026-08-15, sections 5.1 et 7) ──
        Route::post('/{article}/generate-prompt', [NewsCompositionController::class, 'generatePrompt'])->name('generate-prompt');
        Route::post('/{article}/proof-pairs', [NewsCompositionController::class, 'storeProofPair'])->name('proof-pairs.store');
        Route::delete('/{article}/proof-pairs/{pair}', [NewsCompositionController::class, 'destroyProofPair'])->name('proof-pairs.destroy');
        // ── Lot 4b (design doc 2026-09-03, section 2.5) - outils liés, deux routes dédiées,
        // symétriques des paires de preuve ci-dessus (action immédiate, jamais dans update()) ──
        Route::post('/{article}/related-tools', [NewsCompositionController::class, 'storeRelatedTool'])->name('related-tools.store');
        Route::delete('/{article}/related-tools/{slug}', [NewsCompositionController::class, 'destroyRelatedTool'])->name('related-tools.destroy');
        // ── Phase D (design doc 2026-08-15, sections 5.3 et 5.4) - standard d'images ──
        Route::post('/{article}/generate-image-prompt', [NewsCompositionController::class, 'generateImagePrompt'])->name('generate-image-prompt');
        Route::post('/{article}/image', [NewsCompositionController::class, 'uploadImage'])->name('upload-image');
    });

// ── Générateur d'objectif vidéo (admin, superadmin uniquement) ──
// Aucune intégration serveur avec le Prompteur public (100% client-side) : le texte généré
// ici est copié-collé manuellement dans le champ « Objectif de la vidéo » du Prompteur BYOA.
Route::prefix('admin/objectif-video')
    ->name('admin.news.video-goal.')
    ->middleware(['web', 'auth', 'two.factor', \Modules\Authors\Http\Middleware\EnsureSuperAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        Route::get('/', [VideoGoalBuilderController::class, 'index'])->name('index');
        Route::post('/actualites', [VideoGoalBuilderController::class, 'newsForRange'])->name('news');
        Route::post('/generer', [VideoGoalBuilderController::class, 'generateGoal'])->name('generate');
    });
