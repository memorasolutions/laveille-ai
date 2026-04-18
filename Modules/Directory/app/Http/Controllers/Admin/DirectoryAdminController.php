<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Directory\Models\Category;
use Modules\Directory\Models\Tool;
use Intervention\Image\Laravel\Facades\Image;
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

        $tools = $query->orderByDesc('created_at')->paginate((int) Settings::get('directory.admin_per_page', 20))->withQueryString();

        return view('directory::admin.index', compact('tools'));
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
        ]);

        $locale = app()->getLocale();
        $tool->setTranslation('name', $locale, $validated['name']);
        $tool->setTranslation('name', 'fr', $validated['name']);
        $tool->setTranslation('description', $locale, $validated['description'] ?? '');
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

    public function uploadScreenshot(Request $request, Tool $tool): RedirectResponse
    {
        $request->validate([
            'screenshot' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        try {
            $slug = $tool->getTranslation('slug', 'fr_CA') ?: $tool->slug;
            $filePath = "screenshots/{$slug}.jpg";
            $fullPath = public_path($filePath);

            if (file_exists($fullPath)) {
                @copy($fullPath, "{$fullPath}.bak");
            }

            $image = Image::read($request->file('screenshot')->getRealPath())
                ->cover(1200, 630)
                ->toJpeg(85);

            file_put_contents($fullPath, $image);

            $tool->screenshot = $filePath;
            $tool->saveQuietly();

            return back()->with('success', __('Screenshot uploadé avec succès (redimensionné 1200×630).'));
        } catch (\Throwable $e) {
            return back()->with('error', __('Échec upload : :msg', ['msg' => $e->getMessage()]));
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

    public function destroy(Tool $tool): RedirectResponse
    {
        $tool->delete();
        $referer = url()->previous();

        if (str_contains($referer, '/annuaire/') && !str_contains($referer, '/admin/')) {
            return redirect()->route('directory.index')->with('success', __('Outil supprimé.'));
        }

        return redirect()->route('admin.directory.index')->with('success', __('Outil supprimé.'));
    }
}
