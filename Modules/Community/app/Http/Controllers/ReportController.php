<?php

declare(strict_types=1);

namespace Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Community\Models\Report;

class ReportController extends Controller
{
    /**
     * Motifs de signalement (taxonomie unique, partagée par tous les
     * types de contenu signalables — voir Modules/Journal design doc).
     *
     * @var array<int, string>
     */
    public const REASONS = [
        "Droit d'auteur",
        'Contenu illégal',
        'Harcèlement',
        'Vie privée',
        'Autre',
    ];

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reportable_type' => 'required|string|max:255',
            'reportable_id' => 'required|integer',
            'reason' => ['required', 'string', 'max:255', Rule::in(self::REASONS)],
            'details' => 'nullable|string|max:1000',
        ]);

        $allowedTypes = [
            'Modules\\Blog\\Models\\Article',
            'Modules\\News\\Models\\NewsArticle',
            'Modules\\Dictionary\\Models\\Term',
            'Modules\\Directory\\Models\\Tool',
            'Modules\\Acronyms\\Models\\Acronym',
            'Modules\\Journal\\Models\\Journal',
        ];

        if (!in_array($validated['reportable_type'], $allowedTypes, true)) {
            return response()->json(['error' => 'Type non autorise'], 422);
        }

        if (!class_exists($validated['reportable_type'])) {
            return response()->json(['error' => 'Type invalide'], 422);
        }

        $model = $validated['reportable_type']::find($validated['reportable_id']);
        if (!$model) {
            return response()->json(['error' => 'Contenu introuvable'], 404);
        }

        $existing = Report::where('user_id', auth()->id())
            ->where('reportable_type', $validated['reportable_type'])
            ->where('reportable_id', $validated['reportable_id'])
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return response()->json(['success' => true, 'message' => 'Deja signale']);
        }

        Report::create([
            'reportable_type' => $validated['reportable_type'],
            'reportable_id' => $validated['reportable_id'],
            'user_id' => auth()->id(),
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json(['success' => true, 'message' => 'Signalement envoye']);
    }
}
