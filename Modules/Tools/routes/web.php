<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;
use Modules\Tools\Http\Controllers\Admin\ToolAdminController;
use Modules\Tools\Http\Controllers\PublicCrosswordController;
use Modules\Tools\Http\Controllers\PublicMotdleController;
use Modules\Tools\Http\Controllers\PublicPromptController;
use Modules\Tools\Http\Controllers\PublicQtController;
use Modules\Tools\Http\Controllers\PublicToolController;
use Modules\Tools\Http\Controllers\UserCrosswordController;
use Modules\Tools\Http\Middleware\EnsureCrosswordTester;

Route::middleware('web')->group(function () {
    Route::get('/outils', [PublicToolController::class, 'index'])->name('tools.index')->middleware('cacheResponse:600');

    // P17 #235 — redirects 301 /outil/{slug} (singular, typo legacy) → /outils ou /outils/{slug} si match
    Route::get('/outil/{slug?}', function (?string $slug = null) {
        if (! $slug) {
            return redirect('/outils', 301);
        }
        // Si le slug existe en DB (Tools), redirige vers la fiche, sinon vers l'index /outils
        if (class_exists(\Modules\Tools\Models\Tool::class)) {
            try {
                $exists = \Modules\Tools\Models\Tool::where('slug', $slug)->exists();
                if ($exists) {
                    return redirect('/outils/'.$slug, 301);
                }
            } catch (\Throwable) {
                // ignore — fallback vers /outils
            }
        }
        return redirect('/outils', 301);
    })->where('slug', '[a-z0-9\-]*')->name('tools.singular.redirect');

    // Mots croisés - routes API spécifiques + fiche (S80 #63 : ouvert au public, lockdown #48 retiré)
    // Note : middleware EnsureCrosswordTester conservé en code (Modules/Tools/Http/Middleware/) pour réactivation rapide si besoin
    // Audit OWASP 2026-07-03 : cacheResponse ajouté (manquait ici, présent partout ailleurs sur PublicToolController::show)
    // pour éviter l'écriture DB (views_count + trackUsage) à chaque requête sans aucune protection.
    Route::get('/outils/mots-croises', [PublicToolController::class, 'show'])
        ->defaults('slug', 'mots-croises')
        ->middleware('cacheResponse:600')
        ->name('tools.crossword.fiche');

    Route::post('/outils/mots-croises/generate', [PublicCrosswordController::class, 'generate'])
        ->middleware('throttle:30,60')
        ->name('tools.crossword.generate');
    // S80 cleanup : route ai-suggest-pairs retirée (bouton UI retiré S79, dead code)
    Route::post('/outils/mots-croises/pdf-blank', [PublicCrosswordController::class, 'pdfBlank'])
        ->middleware('throttle:30,60')
        ->name('tools.crossword.pdf-blank');
    Route::post('/outils/mots-croises/pdf-solution', [PublicCrosswordController::class, 'pdfSolution'])
        ->middleware('throttle:30,60')
        ->name('tools.crossword.pdf-solution');
    Route::post('/outils/mots-croises/csv-export', [PublicCrosswordController::class, 'csvExport'])
        ->middleware('throttle:30,60')
        ->name('tools.crossword.csv-export');
    Route::post('/outils/mots-croises/csv-import', [PublicCrosswordController::class, 'csvImport'])
        ->middleware('throttle:30,60')
        ->name('tools.crossword.csv-import');
    Route::get('/outils/mots-croises/csv-template', [PublicCrosswordController::class, 'csvTemplate'])
        ->name('tools.crossword.csv-template');
    // 2026-05-05 #94 : index publique grilles partagées (sans publicId) — DOIT venir AVANT /jeumc/{identifier}
    // Audit OWASP 2026-07-03 : throttle ajouté (recherche LIKE + pagination, aucune protection avant)
    Route::get('/jeumc', [PublicCrosswordController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('tools.crossword.public-index');
    // 2026-05-05 #97 Phase 2 : QR PNG personnalisable — DOIT venir AVANT /jeumc/{identifier} fallback
    // Audit OWASP 2026-07-03 : throttle ajouté (génération PNG coûteuse CPU, aucune protection avant)
    Route::get('/jeumc/{identifier}/qr.png', [PublicCrosswordController::class, 'qrPng'])
        ->where('identifier', '[a-zA-Z0-9_-]+')
        ->middleware('throttle:60,1')
        ->name('tools.crossword.qr');
    // 2026-05-05 #97 Phase 1 : {identifier} accepte custom_slug OU public_id (BC garantie via Model::findByShareIdentifier)
    // Audit OWASP 2026-07-03 : throttle ajouté (écriture DB play_count à chaque requête hors-session)
    Route::get('/jeumc/{identifier}', [PublicCrosswordController::class, 'play'])
        ->where('identifier', '[a-zA-Z0-9_-]+')
        ->middleware('throttle:60,1')
        ->name('tools.crossword.play');
    // Backward compatibility : ancien path /jeu/{identifier} -> redirect 301 vers /jeumc/ (S79+ libère /jeu/ pour outils interactifs futurs)
    Route::get('/jeu/{identifier}', fn (string $identifier) => redirect('/jeumc/'.$identifier, 301))
        ->where('identifier', '[a-zA-Z0-9_-]+');

    // Constructeur de prompts — permalien public de partage + remix (Phase 1, plan approuvé 2026-08-05).
    // Pattern répliqué de /jeumc/{identifier} (PublicCrosswordController::play, déjà éprouvé en
    // production) : SavedPrompt n'a pas de custom_slug (uniquement public_id), donc pas de logique
    // de redirection canonique ici. Vérifié : aucune autre route à un seul segment ne commence par /p/.
    Route::get('/p/{publicId}', [PublicPromptController::class, 'show'])
        ->where('publicId', '[a-zA-Z0-9_-]+')
        ->middleware('throttle:60,1')
        ->name('tools.prompts.share');
    // Endpoint PUBLIC (sans authentification) consommé par ?remix={publicId} du constructeur
    // (constructeur-prompts-core.js) — jamais scopé par user_id, uniquement is_public=true.
    Route::get('/p/{publicId}/remix-data', [PublicPromptController::class, 'remixData'])
        ->where('publicId', '[a-zA-Z0-9_-]+')
        ->middleware('throttle:60,1')
        ->name('tools.prompts.remix-data');

    // #177 Avatar tool — admin-only durant construction (page "En construction" sinon)
    Route::get('/outils/avatar', [\Modules\Tools\Http\Controllers\AvatarController::class, 'index'])
        ->name('tools.avatar.index');
    Route::post('/outils/avatar/save', [\Modules\Tools\Http\Controllers\AvatarController::class, 'save'])
        ->middleware('throttle:30,60')
        ->name('tools.avatar.save');

    // #174 Quête narrative IA — saga « Les Sentiers de l'IA »
    // Module activable via QUEST_ENABLED (.env). Désactivé 2026-05-13 (ROI 0/1 user)
    // — Octopus mascotte préservée (assets/octopus/* + composant x-tools::octopus utilisé dans Brain Dump).
    Route::get('/quete', [\Modules\Tools\Http\Controllers\QuestController::class, 'index'])
        ->name('tools.quest.index');
    // #189 Redirect 301 ch1 ancien slug (Loop) → nouveau (Octopus) — préserve bookmarks/SEO
    Route::permanentRedirect('/quete/ch1-eveil-loop', '/quete/ch1-eveil-octopus');
    Route::post('/quete/login', [\Modules\Tools\Http\Controllers\QuestController::class, 'loginRequest'])
        ->middleware('throttle:5,60')
        ->name('tools.quest.login');
    Route::get('/quete/auth/{token}', [\Modules\Tools\Http\Controllers\QuestController::class, 'authVerify'])
        ->where('token', '[a-zA-Z0-9]{48}')
        ->name('tools.quest.auth');
    Route::post('/quete/logout', [\Modules\Tools\Http\Controllers\QuestController::class, 'logout'])
        ->name('tools.quest.logout');
    Route::get('/quete/{slug}', [\Modules\Tools\Http\Controllers\QuestController::class, 'chapter'])
        ->where('slug', '[a-z0-9-]+')
        ->name('tools.quest.chapter');
    Route::post('/quete/{slug}/complete', [\Modules\Tools\Http\Controllers\QuestController::class, 'complete'])
        ->where('slug', '[a-z0-9-]+')
        ->middleware('throttle:30,60')
        ->name('tools.quest.complete');

    // #194 Brain Dump 2026 — landing tool-first SEO+AEO+GEO+EEAT (auto-route via PublicToolController si Tool DB entry "brain-dump" + view custom existante)
    // Alias FR pur : /outils/vide-cerveau → redirect 301 → /outils/brain-dump
    Route::get('/outils/vide-cerveau', fn () => redirect('/outils/brain-dump', 301));

    // Motdle — jeu quotidien (Wordle FR du vocabulaire tech/IA, mots du glossaire). Déclaré AVANT /outils/{slug}.
    // Motdle RETIRÉ 2026-06-13 (remplacé par QT) — redirection 301 réversible ; controller/service/vue conservés dormants.
    Route::redirect('/outils/motdle', '/outils', 301)->name('tools.motdle');
    // QT — Quotient Techno (quiz). Déclaré AVANT /outils/{slug}.
    Route::get('/outils/qt', [PublicQtController::class, 'play'])->name('tools.qt');
    Route::post('/outils/qt/attempt', [PublicQtController::class, 'attempt'])->name('tools.qt.attempt')->middleware('throttle:20,1');

    // QR code avec expiration — endpoint AJAX (POST). Déclaré AVANT /outils/{slug}.
    Route::post('/outils/code-qr/lien-dynamique', [\Modules\Tools\Http\Controllers\QrDynamicLinkController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('tools.qr.dynamic-link');
    // Domaines disponibles pour le sélecteur QR (GET, lecture seule)
    Route::get('/outils/code-qr/domaines', [\Modules\Tools\Http\Controllers\QrDynamicLinkController::class, 'domains'])
        ->name('tools.qr.domains');

    // #163 : exclure 'sudoku' + #177 'avatar' + 'motdle' + 'qt' (routes fournies par modules/contrôleurs dédiés).
    Route::get('/outils/{slug}', [PublicToolController::class, 'show'])
        ->where('slug', '^(?!sudoku$|sudoku/|avatar$|avatar/|motdle$|motdle/|qt$|qt/).+')
        ->middleware('cacheResponse:600')
        ->name('tools.show');
});

// Espace user authentifie : mes mots croises sauvegardes (S80 #63 : tester guard retiré, lockdown #48 ouvert au public)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/user/mots-croises', [UserCrosswordController::class, 'index'])->name('user.crosswords.index');
    Route::get('/user/mots-croises/{publicId}/edit', [UserCrosswordController::class, 'edit'])->name('user.crosswords.edit');
    // 2026-05-05 #97 Phase 1 : POST mise à jour custom_slug (lien personnalisé)
    Route::post('/user/mots-croises/{publicId}/slug', [UserCrosswordController::class, 'updateSlug'])
        ->where('publicId', '[a-zA-Z0-9_-]+')
        ->name('user.crosswords.update-slug');
    // 2026-05-05 #108 : GET check unicité slug (async feedback)
    Route::get('/api/crossword-presets/check-slug', [UserCrosswordController::class, 'checkSlug'])
        ->name('user.crosswords.check-slug');
    // 2026-05-05 #97 Phase 2 : POST mise à jour qr_options (couleurs, logo, ECC, dot_style)
    Route::post('/user/mots-croises/{publicId}/qr-options', [UserCrosswordController::class, 'updateQrOptions'])
        ->where('publicId', '[a-zA-Z0-9_-]+')
        ->name('user.crosswords.update-qr-options');
    // 2026-05-05 #116 : toggle public/privée par le propriétaire (depuis /jeumc card)
    Route::post('/user/mots-croises/{publicId}/toggle-public', [UserCrosswordController::class, 'togglePublic'])
        ->where('publicId', '[a-zA-Z0-9_-]+')
        ->name('user.crosswords.toggle-public');

    // 2026-07-26 : "Mes prompts" - bibliothèque des prompts sauvegardés (constructeur-prompts),
    // recherche/tags/favoris, intégrée à la navigation "Mon espace" existante.
    // Round 106 (2026-08-01) : gate de révision RETIRÉE de cette route - c'est une LECTURE des
    // données déjà confiées par la personne (accès à ses propres données), pas une utilisation
    // de l'outil. Le gate ne doit protéger que la page de l'outil et la création de nouveau
    // contenu (voir Modules/Tools/routes/api.php, groupe `store`/`update`/`duplicate`).
    // UserPromptController::index() scope déjà par SavedPrompt::forUser(auth()->id()) - aucune
    // fuite IDOR possible en retirant ce gate.
    Route::get('/user/prompts', [\Modules\Tools\Http\Controllers\UserPromptController::class, 'index'])
        ->name('user.prompts.index');
});

Route::middleware(['web', 'auth', \Modules\Core\Http\Middleware\EnsureIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('tools', [ToolAdminController::class, 'index'])->name('tools.index');
    Route::get('tools/create', [ToolAdminController::class, 'create'])->name('tools.create');
    Route::post('tools', [ToolAdminController::class, 'store'])->name('tools.store');
    Route::get('tools/{tool}/edit', [ToolAdminController::class, 'edit'])->name('tools.edit');
    Route::put('tools/{tool}', [ToolAdminController::class, 'update'])->name('tools.update');
    Route::post('tools/{tool}/toggle', [ToolAdminController::class, 'toggleActive'])->name('tools.toggle');
    // 2026-05-05 #115 : modération admin grilles mots-croisés (suppression depuis /jeumc)
    Route::post('jeumc/{publicId}/moderate-delete', [UserCrosswordController::class, 'moderateDelete'])
        ->where('publicId', '[a-zA-Z0-9_-]+')
        ->name('jeumc.moderate-delete');
});
