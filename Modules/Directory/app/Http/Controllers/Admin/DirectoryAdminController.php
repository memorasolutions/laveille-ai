<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Core\Services\ScreenshotUploadService;
use Modules\Directory\Models\Category;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolPricingReport;
use Modules\Directory\Services\ScreenshotFocalService;
use Modules\Directory\Services\ScreenshotMasterDerivationService;
use Modules\Directory\Services\ScreenshotService;
use Modules\Settings\Facades\Settings;

class DirectoryAdminController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tool::with(['categories', 'submitter']);

        if ($request->filled('source') && $request->source === 'community') {
            $query->whereNotNull('submitted_by');
        } elseif ($request->filled('source') && $request->source === 'admin') {
            $query->whereNull('submitted_by');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('affiliate') && $request->affiliate === 'yes') {
            $query->whereNotNull('affiliate_url');
        } elseif ($request->filled('affiliate') && $request->affiliate === 'no') {
            $query->whereNull('affiliate_url');
        }

        $statusCounts = [];
        foreach (\Modules\Directory\Enums\ToolStatus::cases() as $statusCase) {
            $statusCounts[$statusCase->value] = Tool::where('status', $statusCase->value)->count();
        }
        $statusCounts['draft'] = Tool::where('status', 'draft')->count();

        $tools = $query->orderByDesc('created_at')->paginate((int) Settings::get('directory.admin_per_page', 20))->withQueryString();

        return view('directory::admin.index', compact('tools', 'statusCounts'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('sort_order')->get();

        return view('directory::admin.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'review' => 'nullable|string',
            'short_description' => 'nullable|string|max:255',
            'url' => 'nullable|url',
            'pricing' => 'required|in:free,freemium,paid,open_source,enterprise',
            'categories' => 'nullable|array',
            'logo' => 'nullable|image|max:2048',
            'screenshot' => 'nullable|url|max:500',
            'is_featured' => 'nullable|boolean',
            'featured_until' => 'nullable|date',
            'featured_order' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer',
        ]);

        $locale = app()->getLocale();
        $slug = Str::slug($validated['name']);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('directory/logos', 'public');
        }

        $tool = Tool::create([
            'name' => [$locale => $validated['name'], 'fr' => $validated['name']],
            'slug' => [$locale => $slug, 'fr' => $slug],
            'description' => [$locale => $validated['description'] ?? '', 'fr' => $validated['description'] ?? ''],
            'review' => [$locale => $validated['review'] ?? '', 'fr' => $validated['review'] ?? ''],
            'short_description' => [$locale => $validated['short_description'] ?? '', 'fr' => $validated['short_description'] ?? ''],
            'url' => $validated['url'],
            'pricing' => $validated['pricing'],
            'logo' => $logoPath ? 'storage/'.$logoPath : null,
            'screenshot' => $validated['screenshot'] ?? null,
            'is_featured' => $request->boolean('is_featured'),
            'featured_until' => $validated['featured_until'] ?? null,
            'featured_order' => $validated['featured_order'] ?? 0,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        if (! empty($validated['categories'])) {
            $tool->categories()->sync($validated['categories']);
        }

        return redirect()->route('admin.directory.index')->with('success', __('Outil ajouté à l\'annuaire.'));
    }

    public function edit(Tool $tool): View
    {
        $categories = Category::orderBy('sort_order')->get();

        // ACTION: presence du master calculee ici (jamais dans la vue) pour piloter le bloc
        // "Repositionner la vignette" (brique 1) - simple lecture disque, pas de nouvelle colonne.
        // MCP: SELF (< 5 lignes)
        // RAISON: design doc 2026-08-10, brique 1, section UI admin.
        $slug = $tool->getTranslation('slug', 'fr_CA') ?: $tool->slug;
        $screenshotMasterRelative = $slug ? "screenshots/masters/{$slug}.jpg" : null;
        $hasScreenshotMaster = $screenshotMasterRelative && \Illuminate\Support\Facades\File::exists(public_path($screenshotMasterRelative));
        $screenshotMasterUrl = $hasScreenshotMaster ? asset($screenshotMasterRelative) : null;
        // ACTION: transmet l'ecart maitre/capture courante a la vue (correctif 2026-08-14) - rendu
        // VISIBLE cote admin plutot que tranche automatiquement (principe directeur de la brique 1).
        // MCP: SELF (< 5 lignes)
        // RAISON: Tool::screenshot_master_stale pose par deriveMasterFromUpload() quand une
        // recapture trop courte conserve un master existant.
        $isScreenshotMasterStale = (bool) $tool->screenshot_master_stale;

        return view('directory::admin.edit', compact('tool', 'categories', 'hasScreenshotMaster', 'screenshotMasterUrl', 'isScreenshotMasterStale'));
    }

    public function update(Request $request, Tool $tool): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:255',
            'url' => 'nullable|url',
            'affiliate_url' => 'nullable|url',
            'pricing' => 'required|in:free,freemium,paid,open_source,enterprise',
            'status' => 'nullable|in:published,pending,draft',
            'categories' => 'nullable|array',
            'logo' => 'nullable|image|max:2048',
            'screenshot' => 'nullable|string|max:500',
            'is_featured' => 'nullable|boolean',
            'featured_until' => 'nullable|date',
            'featured_order' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer',
            'lifecycle_status' => 'nullable|in:active,beta,closed,acquired,renamed,pivoted,paused,scam',
            'lifecycle_date' => 'nullable|date',
            'lifecycle_replacement_url' => 'nullable|url|max:500',
            'lifecycle_replacement_tool_id' => 'nullable|integer|exists:directory_tools,id',
            'lifecycle_notes' => 'nullable|string|max:2000',
            'review' => 'nullable|string',
            'education_discount_type' => ['nullable', 'string', Rule::in(['teacher_free', 'teacher_discount', 'institution_discount', 'quote_only', 'university_license', 'student_discount'])],
            'education_target_audience' => ['nullable', 'array'],
            'education_target_audience.*' => ['string', Rule::in(['K12', 'higher_ed', 'district', 'homeschool', 'individual_teacher'])],
            'education_verification_required' => ['nullable', 'boolean'],
            'education_official_url' => ['nullable', 'url', 'max:500'],
            'education_last_checked_at' => ['nullable', 'date'],
            'is_academic_discount' => ['nullable', 'boolean'],
            'education_level' => ['nullable', 'array'],
            'education_level.*' => ['string', 'in:primaire,secondaire,superieur'],
            'privacy_compliance' => ['nullable', 'string', 'max:100'],
            'learning_curve' => ['nullable', 'integer', 'between:1,5'],
            'has_api_access' => ['nullable', 'boolean'],
        ]);

        $locale = app()->getLocale();
        $tool->setTranslation('name', $locale, $validated['name']);
        $tool->setTranslation('name', 'fr', $validated['name']);
        $tool->setTranslation('description', $locale, $validated['description'] ?? '');
        $tool->setTranslation('review', $locale, $validated['review'] ?? '');
        $tool->setTranslation('short_description', $locale, $validated['short_description'] ?? '');
        $tool->url = $validated['url'];
        $tool->affiliate_url = $validated['affiliate_url'] ?? null;
        $tool->pricing = $validated['pricing'];
        $tool->screenshot = $validated['screenshot'] ?? $tool->screenshot;
        $tool->status = $validated['status'] ?? $tool->status;
        $tool->is_featured = $request->boolean('is_featured');
        $tool->featured_until = $validated['featured_until'] ?? null;
        $tool->featured_order = $validated['featured_order'] ?? 0;
        $tool->sort_order = $validated['sort_order'] ?? 0;

        $tool->lifecycle_status = $validated['lifecycle_status'] ?? $tool->lifecycle_status;
        $tool->lifecycle_date = $validated['lifecycle_date'] ?? null;
        $tool->lifecycle_replacement_url = $validated['lifecycle_replacement_url'] ?? null;
        $tool->lifecycle_replacement_tool_id = $validated['lifecycle_replacement_tool_id'] ?? null;
        $tool->lifecycle_notes = $validated['lifecycle_notes'] ?? null;

        $tool->education_discount_type = $validated['education_discount_type'] ?? null;
        $tool->education_target_audience = $validated['education_target_audience'] ?? null;
        $tool->education_verification_required = $request->boolean('education_verification_required');
        $tool->education_official_url = $validated['education_official_url'] ?? null;
        $tool->education_last_checked_at = $validated['education_last_checked_at'] ?? null;

        $tool->is_academic_discount = $request->boolean('is_academic_discount');
        $tool->education_level = $validated['education_level'] ?? null;
        $tool->privacy_compliance = $validated['privacy_compliance'] ?? null;
        $tool->learning_curve = $validated['learning_curve'] ?? null;
        $tool->has_api_access = $request->boolean('has_api_access');

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('directory/logos', 'public');
            $tool->logo = 'storage/'.$logoPath;
        }

        $tool->save();

        $tool->categories()->sync($validated['categories'] ?? []);

        return redirect()->route('admin.directory.index')->with('success', __('Outil mis à jour.'));
    }

    public function captureScreenshot(Tool $tool): RedirectResponse
    {
        if (! ScreenshotService::isAvailable()) {
            return back()->with('error', __('Service de capture indisponible (Node.js ou script manquant).'));
        }

        \Modules\Directory\Jobs\CaptureScreenshotJob::dispatch($tool);

        return back()->with('success', __('Capture screenshot lancée en arrière-plan. Rafraîchissez la page dans 2-5 minutes (job Puppeteer 180-270 s).'));
    }

    public function uploadScreenshot(Request $request, Tool $tool, ScreenshotUploadService $uploader)
    {
        $request->validate(['screenshot' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120']);

        $wantsJson = $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';
        $slug = $tool->getTranslation('slug', 'fr_CA') ?: $tool->slug;
        $filePath = "screenshots/{$slug}.jpg";

        // ACTION: derivation d'un master a partir du fichier source brut, AVANT l'appel au
        // service partage (qui reste, lui, strictement inchange - contrat News).
        // MCP: SELF (orchestration, logique dans deriveMasterFromUpload())
        // RAISON: design doc 2026-08-10, brique 1, upload manuel ; statut retourne corrige le
        // 2026-08-14 (le point focal et l'indicateur de peremption suivent desormais ce que la
        // derivation a reellement produit, plus un reset aveugle a chaque upload reussi).
        $masterStatus = $this->deriveMasterFromUpload($request->file('screenshot'), $slug);

        $result = $uploader->upload(
            $request->file('screenshot'),
            $filePath,
            $tool,
            'screenshot',
            prefixSlash: false,
            postUpload: fn ($model, $fullPath, $rel) => ScreenshotService::purgeCloudflareFile($rel),
        );

        if ($result['ok']) {
            // Verrouille le screenshot manuel : la régénération automatique ne doit jamais l'écraser.
            $tool->screenshot_locked = true;

            // ACTION: le point focal et le marqueur de péremption ne sont touchés QUE si un nouveau
            // maître valide vient d'être dérivé - jamais sur un maître conservé (stale) ni sur une
            // absence de maître, pour ne jamais effacer un point focal réglé par l'administrateur.
            // MCP: SELF (< 5 lignes)
            // RAISON: correctif 2026-08-14 (perte de travail admin silencieuse, principe directeur
            // « ne jamais détruire le travail de l'administrateur ») - remplace l'ancien reset
            // inconditionnel de screenshot_focal_y à chaque upload réussi.
            if ($masterStatus === self::MASTER_STATUS_CREATED) {
                $tool->screenshot_focal_y = 0;
                $tool->screenshot_master_stale = false;
            } elseif ($masterStatus === self::MASTER_STATUS_KEPT_STALE) {
                $tool->screenshot_master_stale = true;
            }

            $tool->saveQuietly();

            return $wantsJson
                ? response()->json(['ok' => true, 'message' => $result['message'], 'screenshot_url' => $result['url']])
                : back()->with('success', $result['message']);
        }

        return $wantsJson
            ? response()->json(['ok' => false, 'message' => $result['message']], 422)
            : back()->with('error', $result['message']);
    }

    /**
     * Statuts retournes par deriveMasterFromUpload() - pilotent, cote appelant, la remise a zero
     * du point focal et le marqueur de peremption (jamais un reset aveugle a chaque upload).
     */
    private const MASTER_STATUS_CREATED = 'created';

    private const MASTER_STATUS_KEPT_STALE = 'kept_stale';

    private const MASTER_STATUS_NONE = 'none';

    /**
     * Brique 1 (design doc 2026-08-10) - derive un master pour le point focal a partir du fichier
     * uploade brut. La regle scale-puis-teste (largeur ramenee a THUMB_WIDTH EN PREMIER, hauteur
     * RESULTANTE comparee au minimum) vit desormais dans ScreenshotMasterDerivationService,
     * extraite le 2026-08-14 pour etre reutilisee sans duplication par la commande de backfill
     * (directory:backfill-screenshot-masters) - ce mapping ne fait plus qu'orchestrer le statut
     * retourne vers le vocabulaire prive de ce controleur. N'affecte JAMAIS
     * ScreenshotUploadService::upload() (contrat partage avec News, laisse strictement inchange).
     *
     * Principe directeur (correctif 2026-08-14, remplace la decision du 2026-08-10) : ne jamais
     * detruire le travail de l'administrateur. Quand la nouvelle capture, une fois mise a
     * l'echelle, n'atteint pas la hauteur minimale requise :
     * - un master EXISTANT (et le point focal associe) est desormais CONSERVE intact - plus
     *   jamais supprime silencieusement (l'ancienne version #2 du 2026-08-10 l'effacait, ce qui
     *   effacait par ricochet un cadrage regle a la main par l'administrateur) ;
     * - l'evenement est journalise avec son motif, sur le canal dedie 'directory_screenshots'
     *   (config/logging.php, niveau fixe, correctif #1840 du 2026-08-14 - avant ce correctif,
     *   aucune ligne n'etait ecrite ici, ce qui rendait l'ecart invisible en dehors de la fiche
     *   admin elle-meme) ;
     * - l'ecart entre le master conserve et la capture courante devient VISIBLE cote admin via
     *   Tool::screenshot_master_stale, jamais tranche automatiquement (cf. appelant dans
     *   uploadScreenshot()).
     * - si aucun master n'existait avant, rien ne change (comportement inchange).
     */
    private function deriveMasterFromUpload(\Illuminate\Http\UploadedFile $file, string $slug): string
    {
        $masterPath = public_path("screenshots/masters/{$slug}.jpg");
        $hadExistingMaster = \Illuminate\Support\Facades\File::exists($masterPath);

        $status = (new ScreenshotMasterDerivationService())->deriveFromSourcePath($file->getRealPath(), $slug);

        if ($status === ScreenshotMasterDerivationService::STATUS_CREATED) {
            return self::MASTER_STATUS_CREATED;
        }

        if ($hadExistingMaster) {
            // ACTION: journalise l'evenement "maitre de vignette conserve mais perime", motif inclus.
            // MCP: SELF (< 5 lignes)
            // RAISON: correctif #1840 (3e occurrence du piege LOG_LEVEL=error qui avale
            // Log::info/warning avant ecriture en production) - canal dedie 'directory_screenshots'
            // (config/logging.php, niveau fixe, meme parade que 'fusion'/'quality_gate'), jamais
            // Log::error (l'ecart est une decision assumee de conservation, pas une erreur reelle).
            \Illuminate\Support\Facades\Log::channel('directory_screenshots')->info(
                "SCREENSHOT-MASTER-STALE: maitre conserve mais perime pour {$slug} (motif: {$status}) - ".
                'recapture insuffisante, ancien maitre et point focal intacts, ecart signale a l\'admin.'
            );

            return self::MASTER_STATUS_KEPT_STALE;
        }

        return self::MASTER_STATUS_NONE;
    }

    /**
     * Brique 1 (design doc 2026-08-10) - applique un nouveau point focal vertical sur le master
     * existant et redirige/renvoie la nouvelle URL cache-bustee de la vignette derivee.
     */
    public function setFocal(Request $request, Tool $tool, ScreenshotFocalService $focalService): \Illuminate\Http\JsonResponse
    {
        $request->validate(['focal_y' => 'required|integer']);

        // ACTION: clamp systematique cote serveur, jamais confiance dans la valeur brute du formulaire
        // MCP: SELF (< 5 lignes)
        // RAISON: design doc 2026-08-10, section 6 (securite), CA-2 - meme si l'UI JS applique deja
        // une borne cote client, le serveur ne lui fait jamais confiance.
        $tool->screenshot_focal_y = max(0, min((int) $request->input('focal_y'), 770));

        if (! $focalService->deriveThumbnail($tool)) {
            return response()->json([
                'ok' => false,
                'message' => __("Recadrage impossible (master introuvable ou illisible). Une nouvelle capture est nécessaire."),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => __('Cadrage appliqué.'),
            'screenshot_url' => asset($tool->screenshot).'?v='.$tool->updated_at->timestamp,
            'focal_y' => $tool->screenshot_focal_y,
        ]);
    }

    public function setMainScreenshot(Tool $tool, int $screenshotId): RedirectResponse
    {
        $screenshot = $tool->screenshots()->findOrFail($screenshotId);

        $tool->screenshot = $screenshot->image_path;
        $tool->save();

        if (! $screenshot->is_approved) {
            $screenshot->is_approved = true;
            $screenshot->save();
        }

        return back()->with('success', __('Screenshot principal mis a jour.'));
    }

    public function toggleFeatured(Request $request, Tool $tool): RedirectResponse
    {
        $request->validate(['duration_days' => 'nullable|integer|min:1|max:365']);

        if ($tool->isSponsored()) {
            $tool->deactivateSponsorship();
            $msg = __(':name désactivé du sponsoring.', ['name' => $tool->name]);
        } else {
            $days = $request->integer('duration_days') ?: 30;
            $tool->activateSponsorship($days);
            $msg = __(':name activé en sponsorisé jusqu\'au :date.', ['name' => $tool->name, 'date' => format_date($tool->featured_until)]);
        }

        activity('directory')->performedOn($tool)->causedBy(auth()->user())->log('tool_featured_toggled');

        if (class_exists(\Spatie\ResponseCache\Facades\ResponseCache::class)) {
            try { \Spatie\ResponseCache\Facades\ResponseCache::clear(); } catch (\Throwable $e) {}
        }

        return back()->with('success', $msg);
    }

    public function destroy(Tool $tool): RedirectResponse
    {
        $tool->delete();
        $referer = url()->previous();

        if (str_contains($referer, '/annuaire/') && !str_contains($referer, '/admin/')) {
            return redirect()->route('directory.index')->with('success', __('Outil supprimé.'));
        }

        return redirect()->route('admin.directory.index')->with('success', __('Outil supprimé.'));
    }

    /**
     * Autosave draft for a tool.
     */
    public function autosave(\Illuminate\Http\Request $request, \Modules\Directory\Models\Tool $tool): \Illuminate\Http\JsonResponse
    {
        abort_unless($request->user()?->can('update', $tool) ?? true, 403);

        $validated = $request->validate([
            'name' => 'nullable|string',
            'description' => 'nullable|string',
            'review' => 'nullable|string',
            'short_description' => 'nullable|string',
            'url' => 'nullable|string',
            'how_to_use' => 'nullable|string',
        ]);

        $tool->fill(array_filter($validated, fn ($v) => $v !== null));

        if ($tool->isDirty()) {
            $tool->saveQuietly();

            return response()->json([
                'success' => true,
                'saved_at' => now()->toDateTimeString(),
            ]);
        }

        return response()->json([
            'success' => true,
            'saved_at' => null,
        ]);
    }

    public function settings(): View
    {
        $defaultSort = Settings::get('directory.default_sort', 'random');

        $sortOptions = [
            'random'  => __('Hasard (par défaut)'),
            'popular' => __('Populaires (plus cliqués)'),
            'recent'  => __('Récents (plus récents)'),
            'name'    => __('Alphabétique (A-Z)'),
        ];

        return view('directory::admin.settings', compact('defaultSort', 'sortOptions'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'default_sort' => 'required|in:random,popular,recent,name',
        ]);

        Settings::set('directory.default_sort', $request->default_sort);

        return redirect()
            ->route('admin.directory.settings')
            ->with('success', __('Ordre de tri par défaut mis à jour.'));
    }

    public function pricingDrift(Request $request): View
    {
        $cutoff90 = now()->subDays(90);

        $query = Tool::published()->notArchived()
            ->where(function ($q) use ($cutoff90) {
                $q->where('last_enriched_at', '<', $cutoff90)
                  ->orWhereNull('last_enriched_at');
            });

        $healthMetrics = Tool::healthMetrics();
        $totalDrifted = $healthMetrics['drift_90'];
        $neverChecked = $healthMetrics['never_checked'];
        $criticalDrift = $healthMetrics['drift_180'];
        $distribution = $healthMetrics['distribution'];

        $tools = $query->orderBy('last_enriched_at', 'asc')
                       ->paginate(50)
                       ->withQueryString();

        $autoFlaggedPending = ToolPricingReport::pending()->autoFlagged()->count();
        $userSubmittedPending = ToolPricingReport::pending()->userSubmitted()->count();

        return view('directory::admin.pricing-drift', compact('tools', 'totalDrifted', 'neverChecked', 'criticalDrift', 'distribution', 'autoFlaggedPending', 'userSubmittedPending'));
    }
}
