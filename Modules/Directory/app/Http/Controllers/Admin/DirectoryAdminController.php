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

        return view('directory::admin.edit', compact('tool', 'categories'));
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

        $result = $uploader->upload(
            $request->file('screenshot'),
            $filePath,
            $tool,
            'screenshot',
            prefixSlash: false,
            postUpload: fn ($model, $fullPath, $rel) => $this->purgeCloudflareScreenshot($rel),
        );

        if ($result['ok']) {
            return $wantsJson
                ? response()->json(['ok' => true, 'message' => $result['message'], 'screenshot_url' => $result['url']])
                : back()->with('success', $result['message']);
        }

        return $wantsJson
            ? response()->json(['ok' => false, 'message' => $result['message']], 422)
            : back()->with('error', $result['message']);
    }

    private function purgeCloudflareScreenshot(string $filePath): void
    {
        try {
            $zoneId = env('CLOUDFLARE_ZONE_ID');
            $apiToken = env('CLOUDFLARE_API_TOKEN');
            if (empty($zoneId) || empty($apiToken)) {
                return;
            }
            \Illuminate\Support\Facades\Http::timeout(8)
                ->withToken($apiToken)
                ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
                    'files' => [config('app.url').'/'.ltrim($filePath, '/')],
                ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Cloudflare purge failed: '.$e->getMessage());
        }
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
            $msg = __(':name activé en sponsorisé jusqu\'au :date.', ['name' => $tool->name, 'date' => $tool->featured_until->format('d/m/Y')]);
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
        $cutoff180 = now()->subDays(180);

        $query = Tool::published()
            ->where(function ($q) use ($cutoff90) {
                $q->where('last_enriched_at', '<', $cutoff90)
                  ->orWhereNull('last_enriched_at');
            });

        $totalDrifted = (clone $query)->count();
        $neverChecked = Tool::published()->whereNull('last_enriched_at')->count();
        $criticalDrift = Tool::published()->where('last_enriched_at', '<', $cutoff180)->count();

        $tools = $query->orderBy('last_enriched_at', 'asc')
                       ->paginate(50)
                       ->withQueryString();

        return view('directory::admin.pricing-drift', compact('tools', 'totalDrifted', 'neverChecked', 'criticalDrift'));
    }
}
