<?php

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Tools\Models\SavedPrompt;

class PublicPromptController
{
    public function index(Request $request): View
    {
        $query = $request->get('q', '');
        $sort = $request->get('sort', 'recent');

        $prompts = SavedPrompt::public()->with('user:id,name');

        if ($query) {
            $searchTerm = $query;
            $prompts->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('prompt_text', 'LIKE', "%{$searchTerm}%");
            });
        }

        match ($sort) {
            'oldest' => $prompts->oldest(),
            'alpha' => $prompts->orderBy('name'),
            default => $prompts->latest(),
        };

        $prompts = $prompts->paginate(12)->appends($request->all());

        return view('tools::public.prompts.index', compact('prompts', 'query', 'sort'));
    }
}
