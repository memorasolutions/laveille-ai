<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Directory\Models\Category;
use Modules\Directory\Models\Tool;

class IngestController
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (! $token || $token !== config('directory.ingest_token')) {
            return response()->json([
                'success' => false,
                'message' => 'Jeton d\'authentification invalide ou manquant.',
            ], 401);
        }

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'pricing' => ['required', Rule::in(['free', 'freemium', 'paid', 'open_source', 'enterprise'])],
            'screenshot' => ['nullable', 'url', 'max:500'],
            'source' => ['nullable', 'string', 'max:100'],
            'tutorials' => ['nullable', 'array'],
            'tutorials.*.title' => ['required_with:tutorials', 'string', 'max:255'],
            'tutorials.*.url' => ['required_with:tutorials', 'url', 'max:2048'],
            'tutorials.*.type' => ['required_with:tutorials', Rule::in(['youtube', 'article', 'doc'])],
            'tutorials.*.language' => ['required_with:tutorials', Rule::in(['fr', 'en'])],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'max:100'],
        ]);

        // Détection de doublon par domaine
        $incomingHost = parse_url($validated['url'], PHP_URL_HOST);
        $incomingHost = preg_replace('/^www\./', '', $incomingHost ?? '');

        if ($incomingHost) {
            $existingTool = Tool::where('url', 'LIKE', '%'.$incomingHost.'%')->first();

            if ($existingTool) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un outil avec le même domaine existe déjà.',
                    'duplicate' => [
                        'id' => $existingTool->id,
                        'name' => $existingTool->getTranslation('name', 'fr_CA', false),
                        'slug' => $existingTool->getTranslation('slug', 'fr_CA', false),
                        'url' => $existingTool->url,
                        'status' => $existingTool->status,
                    ],
                ], 409);
            }
        }

        // Dédup par nom (fuzzy matching — catch "DeepSeek" vs "Deep Seek AI")
        $incomingNameNorm = strtolower(trim(preg_replace('/\s*(ai|tool|app)\s*$/i', '', $validated['name'])));
        $allTools = Tool::select('id', 'name', 'slug', 'url', 'status')->get();
        foreach ($allTools as $existing) {
            $existingName = strtolower(trim($existing->getTranslation('name', 'fr_CA', false) ?? ''));
            similar_text($incomingNameNorm, $existingName, $percent);
            if ($percent > 85) {
                return response()->json([
                    'success' => false,
                    'message' => "Un outil similaire existe déjà (correspondance {$percent}%).",
                    'duplicate' => [
                        'id' => $existing->id,
                        'name' => $existing->getTranslation('name', 'fr_CA', false),
                        'slug' => $existing->getTranslation('slug', 'fr_CA', false),
                        'url' => $existing->url,
                        'status' => $existing->status,
                        'similarity' => round($percent, 1),
                    ],
                ], 409);
            }
        }

        // Slug unique
        $locale = 'fr_CA';
        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (Tool::where("slug->{$locale}", $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        // Créer l'outil en statut pending
        $tool = new Tool;
        $tool->setTranslation('name', $locale, $validated['name']);
        $tool->setTranslation('slug', $locale, $slug);
        $tool->setTranslation('description', $locale, $validated['description'] ?? '');
        $tool->setTranslation('short_description', $locale, $validated['short_description'] ?? Str::limit($validated['description'] ?? '', 200));
        $tool->url = $validated['url'];
        $tool->pricing = $validated['pricing'];
        $tool->status = 'pending';
        $tool->is_featured = false;
        $tool->clicks_count = 0;
        $tool->sort_order = 0;
        $tool->submitted_by = null;
        $tool->screenshot = $validated['screenshot'] ?? null;

        // Métadonnées (source + tutorials)
        $metadata = [];
        if (! empty($validated['source'])) {
            $metadata['source'] = $validated['source'];
        }
        if (! empty($validated['tutorials'])) {
            $metadata['tutorials'] = $validated['tutorials'];
        }
        if (! empty($metadata) && Schema::hasColumn('directory_tools', 'metadata')) {
            $tool->metadata = $metadata;
        }

        $tool->save();

        // Tutoriels via resources() si disponible
        if (! empty($validated['tutorials']) && method_exists($tool, 'resources')) {
            foreach ($validated['tutorials'] as $tutorial) {
                try {
                    $tool->resources()->create([
                        'title' => $tutorial['title'],
                        'url' => $tutorial['url'],
                        'type' => $tutorial['type'],
                        'language' => $tutorial['language'] ?? 'fr',
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Tutoriel non sauvegardé pour outil #'.$tool->id, ['error' => $e->getMessage()]);
                }
            }
        }

        // Catégories par slug
        if (! empty($validated['categories'])) {
            $categoryIds = Category::where(function ($q) use ($validated) {
                $q->whereIn('slug->fr_CA', $validated['categories'])
                    ->orWhereIn('slug->fr', $validated['categories']);
            })->pluck('id')->toArray();

            if (! empty($categoryIds)) {
                $tool->categories()->attach($categoryIds);
            }
        }

        // Notifier les admins
        // Notification admin (skip si ToolSubmittedNotification exige un User submitter)
        try {
            $notifClass = \Modules\Directory\Notifications\ToolSubmittedNotification::class;
            if (class_exists($notifClass)) {
                $ref = new \ReflectionMethod($notifClass, '__construct');
                $params = $ref->getParameters();
                // Si la notification accepte un User nullable en 2e param, on l'envoie
                if (count($params) >= 2 && $params[1]->allowsNull()) {
                    $admins = \App\Models\User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'super_admin']))->get();
                    foreach ($admins as $admin) {
                        $admin->notify(new $notifClass($tool, null));
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Notification admin échouée pour outil #'.$tool->id, ['error' => $e->getMessage()]);
        }

        Log::info('Outil ingéré via API n8n', [
            'tool_id' => $tool->id,
            'name' => $validated['name'],
            'url' => $validated['url'],
            'source' => $validated['source'] ?? 'unknown',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Outil soumis avec succès. Il sera révisé avant publication.',
            'data' => [
                'id' => $tool->id,
                'slug' => $slug,
                'name' => $validated['name'],
                'status' => 'pending',
            ],
        ], 201);
    }
}
