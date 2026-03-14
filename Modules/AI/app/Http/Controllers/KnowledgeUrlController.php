<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\AI\Models\KnowledgeDocument;
use Modules\AI\Models\KnowledgeUrl;
use Modules\AI\Services\WebScraperService;

class KnowledgeUrlController extends Controller
{
    public function __construct(
        private readonly WebScraperService $scraperService
    ) {}

    public function index(Request $request): View
    {
        $validStatuses = ['pending', 'scraping', 'completed', 'failed', 'robots_blocked'];

        $query = KnowledgeUrl::withCount('documents')->latest();

        if ($request->filled('scrape_status') && in_array($request->input('scrape_status'), $validStatuses, true)) {
            $query->where('scrape_status', $request->input('scrape_status'));
        }

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('label', 'like', '%'.$search.'%')
                    ->orWhere('url', 'like', '%'.$search.'%');
            });
        }

        $urls = $query->paginate(20)->appends($request->query());

        return view('ai::admin.urls.index', compact('urls'));
    }

    public function create(): View
    {
        return view('ai::admin.urls.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => 'required|url|max:500',
            'label' => 'required|max:255',
            'hidden_source_name' => 'nullable|max:255',
            'max_pages' => 'required|integer|min:1|max:200',
            'scrape_frequency' => 'required|in:manual,daily,weekly,monthly',
            'is_active' => 'boolean',
        ]);

        KnowledgeUrl::create([
            'url' => $validated['url'],
            'label' => $validated['label'],
            'hidden_source_name' => $validated['hidden_source_name'] ?? null,
            'max_pages' => $validated['max_pages'],
            'scrape_frequency' => $validated['scrape_frequency'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.ai.urls.index')
            ->with('success', 'Source URL ajoutée à la base de connaissances.');
    }

    public function edit(KnowledgeUrl $url): View
    {
        $documentsCount = $url->documents()->count();

        return view('ai::admin.urls.edit', compact('url', 'documentsCount'));
    }

    public function update(Request $request, KnowledgeUrl $url): RedirectResponse
    {
        $validated = $request->validate([
            'url' => 'required|url|max:500',
            'label' => 'required|max:255',
            'hidden_source_name' => 'nullable|max:255',
            'max_pages' => 'required|integer|min:1|max:200',
            'scrape_frequency' => 'required|in:manual,daily,weekly,monthly',
            'is_active' => 'boolean',
        ]);

        $url->update([
            'url' => $validated['url'],
            'label' => $validated['label'],
            'hidden_source_name' => $validated['hidden_source_name'] ?? null,
            'max_pages' => $validated['max_pages'],
            'scrape_frequency' => $validated['scrape_frequency'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.ai.urls.index')
            ->with('success', 'Source URL mise à jour.');
    }

    public function destroy(KnowledgeUrl $url): RedirectResponse
    {
        KnowledgeDocument::where('source_type', 'url')
            ->where('source_id', $url->id)
            ->delete();

        $url->delete();

        return redirect()->route('admin.ai.urls.index')
            ->with('success', 'Source URL et tous ses documents supprimés.');
    }

    public function checkRobots(Request $request): JsonResponse
    {
        $request->validate(['url' => 'required|url']);

        $targetUrl = $request->input('url');
        $allowed = $this->scraperService->checkRobotsTxt($targetUrl);

        return response()->json([
            'allowed' => $allowed,
            'message' => $allowed
                ? 'Le scraping est autorisé par robots.txt.'
                : 'Le scraping est bloqué par robots.txt.',
        ]);
    }

    public function scrape(KnowledgeUrl $url): RedirectResponse
    {
        $count = $this->scraperService->scrapeAndIndex($url);

        if ($url->scrape_status === 'robots_blocked') {
            return redirect()->back()
                ->with('error', 'Scraping bloqué par robots.txt. Aucune page indexée.');
        }

        if ($url->scrape_status === 'failed') {
            return redirect()->back()
                ->with('error', 'Le scraping a échoué : '.($url->scrape_error ?? 'erreur inconnue'));
        }

        return redirect()->back()
            ->with('success', $count.' page(s) indexée(s) avec succès.');
    }
}
