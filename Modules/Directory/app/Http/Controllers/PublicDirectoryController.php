<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Directory\Models\Category;
use Modules\Directory\Models\Tool;

class PublicDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tool::published()->with('categories', 'tags')->orderBy('sort_order');

        if ($request->filled('pricing')) {
            $query->where('pricing', $request->pricing);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $locale = app()->getLocale();
            $query->where("name->{$locale}", 'like', "%{$search}%");
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', fn ($q) => $q->where("slug->" . app()->getLocale(), $request->category));
        }

        $tools = $query->get();
        $categories = Category::orderBy('sort_order')->get();
        $pricingOptions = ['free' => __('Gratuit'), 'freemium' => __('Freemium'), 'paid' => __('Payant'), 'open_source' => __('Open source'), 'enterprise' => __('Entreprise')];

        return view('directory::public.index', compact('tools', 'categories', 'pricingOptions'));
    }

    public function show(string $slug): View
    {
        $tool = Tool::published()
            ->where('slug->' . app()->getLocale(), $slug)
            ->with('categories', 'tags')
            ->firstOrFail();

        $tool->increment('clicks_count');

        $similarTools = Tool::published()
            ->where('id', '!=', $tool->id)
            ->whereHas('categories', function ($q) use ($tool) {
                $q->whereIn('directory_categories.id', $tool->categories->pluck('id'));
            })
            ->limit(4)
            ->get();

        return view('directory::public.show', compact('tool', 'similarTools'));
    }
}
