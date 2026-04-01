<?php

declare(strict_types=1);

namespace Modules\Community\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CategorySubscriptionController extends Controller
{
    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_tag' => ['required', 'string', 'max:100'],
            'module' => ['required', 'string', 'in:news,blog'],
        ]);

        $subscribed = auth()->user()->subscribeTo($validated['category_tag'], $validated['module']);

        return response()->json([
            'subscribed' => $subscribed,
            'message' => $subscribed
                ? __('Vous suivez maintenant cette categorie.')
                : __('Vous ne suivez plus cette categorie.'),
        ]);
    }
}
