<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\AI\Models\KnowledgeDocument;
use Modules\AI\Services\KnowledgeBaseService;
use Modules\Settings\Facades\Settings;

class KnowledgeBaseController extends Controller
{
    public function __construct(
        private readonly KnowledgeBaseService $knowledgeBaseService
    ) {}

    public function index(Request $request): View
    {
        $query = KnowledgeDocument::withCount('chunks')->latest();

        $validTypes = ['manual', 'faq', 'page', 'article', 'service'];

        if ($request->filled('source_type') && in_array($request->input('source_type'), $validTypes, true)) {
            $query->where('source_type', $request->input('source_type'));
        }

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where('title', 'like', '%'.$search.'%');
        }

        $documents = $query->paginate((int) Settings::get('ai.knowledge_base_per_page', 20))->appends($request->query());

        return view('ai::admin.knowledge.index', compact('documents'));
    }

    public function create(): View
    {
        return view('ai::admin.knowledge.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'source_type' => 'required|in:manual,faq,page,article,service',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $document = $this->knowledgeBaseService->addDocument(
            title: $validated['title'],
            content: $validated['content'],
            sourceType: $validated['source_type'],
            metadata: $validated['metadata'] ?? [],
        );

        $document->update(['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('admin.ai.knowledge.index')
            ->with('success', 'Document ajouté à la base de connaissances.');
    }

    public function show(KnowledgeDocument $knowledge): RedirectResponse
    {
        return redirect()->route('admin.ai.knowledge.edit', $knowledge);
    }

    public function edit(KnowledgeDocument $knowledge): View
    {
        return view('ai::admin.knowledge.edit', ['document' => $knowledge]);
    }

    public function update(Request $request, KnowledgeDocument $knowledge): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'source_type' => 'required|in:manual,faq,page,article,service',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $this->knowledgeBaseService->updateDocument($knowledge, $validated['content']);

        $knowledge->update([
            'title' => $validated['title'],
            'source_type' => $validated['source_type'],
            'metadata' => $validated['metadata'] ?? $knowledge->metadata,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.ai.knowledge.index')
            ->with('success', 'Document mis à jour.');
    }

    public function destroy(KnowledgeDocument $knowledge): RedirectResponse
    {
        $this->knowledgeBaseService->deleteDocument($knowledge);

        return redirect()->route('admin.ai.knowledge.index')
            ->with('success', 'Document supprimé de la base de connaissances.');
    }
}
