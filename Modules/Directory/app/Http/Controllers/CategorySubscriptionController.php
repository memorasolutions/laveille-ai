<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Directory\Models\Category;
use Modules\Directory\Models\UserCategorySubscription;

/**
 * S90 #43 — Souscription/désinscription d'alertes catégorie.
 * Endpoints AJAX simples pour bouton 🔔 sur page catégorie répertoire.
 */
class CategorySubscriptionController extends Controller
{
    private function ensureEnabled(): void
    {
        if (! (bool) config('directory.category_alerts.enabled', false)) {
            abort(404);
        }
    }

    public function toggle(Request $request, string $slug): JsonResponse
    {
        $this->ensureEnabled();

        if (! auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => __('Connectez-vous pour recevoir les alertes.'),
                'login_url' => route('login'),
            ], 401);
        }

        $category = Category::where('slug', $slug)->first();
        if (! $category) {
            return response()->json(['success' => false, 'message' => 'Catégorie introuvable.'], 404);
        }

        $userId = (int) auth()->id();
        $existing = UserCategorySubscription::where('user_id', $userId)
            ->where('directory_category_id', $category->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'success' => true,
                'subscribed' => false,
                'message' => __('Alertes désactivées pour :name.', ['name' => $category->name]),
            ]);
        }

        UserCategorySubscription::create([
            'user_id' => $userId,
            'directory_category_id' => $category->id,
            'frequency' => 'weekly',
        ]);

        return response()->json([
            'success' => true,
            'subscribed' => true,
            'message' => __('Alertes hebdomadaires activées pour :name.', ['name' => $category->name]),
        ]);
    }

    public function status(string $slug): JsonResponse
    {
        $this->ensureEnabled();

        if (! auth()->check()) {
            return response()->json(['subscribed' => false, 'authenticated' => false]);
        }

        $category = Category::where('slug', $slug)->first();
        if (! $category) {
            return response()->json(['subscribed' => false, 'category_found' => false]);
        }

        $exists = UserCategorySubscription::where('user_id', auth()->id())
            ->where('directory_category_id', $category->id)
            ->exists();

        return response()->json([
            'subscribed' => $exists,
            'authenticated' => true,
            'category' => ['id' => $category->id, 'slug' => $category->slug, 'name' => $category->name],
        ]);
    }
}
