<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\Models\Announcement;
use Modules\Settings\Facades\Settings;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $announcements = Announcement::query()
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate((int) Settings::get('backoffice.announcements_per_page', 25))
            ->withQueryString();

        return view('core::admin.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        return view('core::admin.announcements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        if ($validated['is_published'] ?? false) {
            $validated['published_at'] = now();
        }

        Announcement::create($validated);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Annonce créée.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('core::admin.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        if (($validated['is_published'] ?? false) && ! $announcement->is_published) {
            $validated['published_at'] = now();
        } elseif (! ($validated['is_published'] ?? false)) {
            $validated['published_at'] = null;
        }

        $announcement->update($validated);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Annonce mise à jour.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Annonce supprimée.');
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'type' => ['required', 'in:feature,improvement,fix,announcement'],
            'version' => ['nullable', 'string', 'max:20'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
