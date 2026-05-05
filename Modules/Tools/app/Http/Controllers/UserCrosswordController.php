<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Tools\Models\SavedCrosswordPreset;

class UserCrosswordController
{
    public function index(): View
    {
        $presets = SavedCrosswordPreset::forUser((int) auth()->id())
            ->orderByDesc('updated_at')
            ->paginate(20);

        // Compteur de paires depuis config_text (1 ligne = 1 paire)
        $presets->getCollection()->transform(function (SavedCrosswordPreset $p) {
            $lines = preg_split('/\r\n|\n|\r/', trim((string) $p->config_text));
            $p->pairs_count = collect($lines)->filter(fn ($l) => str_contains($l, ' / '))->count();

            return $p;
        });

        return view('tools::user.crosswords.index', compact('presets'));
    }

    public function edit(string $publicId): RedirectResponse
    {
        $preset = SavedCrosswordPreset::where('user_id', auth()->id())
            ->where('public_id', $publicId)
            ->firstOrFail();

        return redirect('/outils/mots-croises?preset='.$preset->public_id);
    }

    /**
     * 2026-05-05 #97 Phase 1 : POST mise à jour custom_slug.
     * Route : POST /user/mots-croises/{publicId}/slug
     * Répond JSON si XHR, redirect back sinon.
     */
    public function updateSlug(Request $request, string $publicId): \Symfony\Component\HttpFoundation\Response
    {
        $preset = SavedCrosswordPreset::where('user_id', auth()->id())
            ->where('public_id', $publicId)
            ->firstOrFail();

        $validated = $request->validate([
            'custom_slug' => [
                'nullable',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('saved_crossword_presets', 'custom_slug')->ignore($preset->id)->whereNull('deleted_at'),
                Rule::notIn(SavedCrosswordPreset::RESERVED_SLUGS),
            ],
        ], [
            'custom_slug.regex' => 'Le lien personnalisé doit contenir uniquement des lettres minuscules, chiffres et tirets.',
            'custom_slug.unique' => 'Ce lien est déjà pris. Choisissez-en un autre.',
            'custom_slug.not_in' => 'Ce lien est réservé. Choisissez-en un autre.',
            'custom_slug.min' => 'Le lien personnalisé doit faire au moins 3 caractères.',
            'custom_slug.max' => 'Le lien personnalisé ne peut pas dépasser 50 caractères.',
        ]);

        $preset->custom_slug = $validated['custom_slug'] ?? null;
        $preset->save();

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'custom_slug' => $preset->custom_slug,
                'share_url' => $preset->share_url,
                'message' => $preset->custom_slug
                    ? 'Lien personnalisé enregistré.'
                    : 'Lien personnalisé retiré.',
            ]);
        }

        $message = $preset->custom_slug
            ? 'Lien personnalisé mis à jour : '.$preset->share_url
            : 'Lien personnalisé retiré.';

        return back()->with('success', $message);
    }

    /**
     * 2026-05-05 #108 : check unicité slug async pour feedback inline.
     * Route : GET /api/crossword-presets/check-slug?slug=xxx&exclude=publicId
     * Retourne {available: bool, suggestion: string|null}.
     */
    public function checkSlug(Request $request): \Illuminate\Http\JsonResponse
    {
        $slug = trim((string) $request->query('slug', ''));
        $excludePublicId = trim((string) $request->query('exclude', ''));

        // Normalisation côté serveur (sécurité = même règle que mutator)
        $slug = trim(preg_replace('/-{2,}/', '-', preg_replace('/[^a-z0-9-]/', '', strtolower(\Illuminate\Support\Str::ascii(str_replace(' ', '-', $slug))))), '-');

        if (strlen($slug) < 3) {
            return response()->json(['available' => false, 'suggestion' => null, 'reason' => 'too_short']);
        }
        if (strlen($slug) > 50) {
            return response()->json(['available' => false, 'suggestion' => null, 'reason' => 'too_long']);
        }
        if (in_array($slug, SavedCrosswordPreset::RESERVED_SLUGS, true)) {
            return response()->json(['available' => false, 'suggestion' => $slug.'-grille', 'reason' => 'reserved']);
        }

        $query = SavedCrosswordPreset::where('custom_slug', $slug);
        if ($excludePublicId !== '') {
            $query->where('public_id', '!=', $excludePublicId);
        }
        $taken = $query->exists();

        if (! $taken) {
            return response()->json(['available' => true, 'suggestion' => null]);
        }

        // Génère suggestion : slug-2, slug-3, ... ou slug-2026 (année courante).
        $suggestion = null;
        for ($i = 2; $i <= 9; $i++) {
            $candidate = $slug.'-'.$i;
            if (! SavedCrosswordPreset::where('custom_slug', $candidate)->exists()) {
                $suggestion = $candidate;
                break;
            }
        }
        if (! $suggestion) {
            $candidate = $slug.'-'.now()->year;
            if (! SavedCrosswordPreset::where('custom_slug', $candidate)->exists()) {
                $suggestion = $candidate;
            }
        }

        return response()->json(['available' => false, 'suggestion' => $suggestion, 'reason' => 'taken']);
    }

    /**
     * 2026-05-05 #97 Phase 2 : POST mise à jour qr_options (couleurs, logo, dot style, ECC).
     * Route : POST /user/mots-croises/{publicId}/qr-options
     */
    /**
     * 2026-05-05 #115 : suppression admin (modération) d'une grille publique depuis /jeumc.
     * Soft delete via Eloquent SoftDeletes. Log via activitylog si dispo.
     * Route : POST /admin/jeumc/{publicId}/moderate-delete (middleware EnsureIsAdmin)
     */
    public function moderateDelete(Request $request, string $publicId): \Illuminate\Http\JsonResponse
    {
        $preset = SavedCrosswordPreset::where('public_id', $publicId)->firstOrFail();
        $name = $preset->name;
        $userId = $preset->user_id;
        $preset->delete(); // soft delete

        // Log activitylog si dispo
        if (class_exists(\Spatie\Activitylog\Facades\Activity::class)) {
            try {
                activity('crossword-moderation')
                    ->performedOn($preset)
                    ->withProperties(['name' => $name, 'public_id' => $publicId, 'owner_user_id' => $userId])
                    ->log('Grille mots-croisés supprimée par modération admin');
            } catch (\Throwable $e) { /* silent */ }
        }

        return response()->json([
            'success' => true,
            'message' => 'Grille « '.$name.' » supprimée.',
        ]);
    }

    public function updateQrOptions(Request $request, string $publicId): \Symfony\Component\HttpFoundation\Response
    {
        $preset = SavedCrosswordPreset::where('user_id', auth()->id())
            ->where('public_id', $publicId)
            ->firstOrFail();

        $validated = $request->validate([
            'foreground' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'background' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'ecc' => ['nullable', 'in:L,M,Q,H'],
            'dot_style' => ['nullable', 'in:square,rounded,dots'],
            'logo' => ['nullable', 'boolean'],
        ], [
            'foreground.regex' => 'Couleur foreground invalide (format #RRGGBB).',
            'background.regex' => 'Couleur background invalide (format #RRGGBB).',
        ]);

        // Filter null/empty et stocker uniquement champs renseignés.
        $opts = array_filter([
            'foreground' => $validated['foreground'] ?? null,
            'background' => $validated['background'] ?? null,
            'ecc' => $validated['ecc'] ?? null,
            'dot_style' => $validated['dot_style'] ?? null,
            'logo' => isset($validated['logo']) ? ((bool) $validated['logo'] ? '1' : '0') : null,
        ], fn ($v) => $v !== null && $v !== '');

        $preset->qr_options = $opts ?: null;
        $preset->save();

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'qr_options' => $preset->qr_options,
                'message' => 'Options QR enregistrées.',
            ]);
        }

        return back()->with('success', 'Options QR enregistrées.');
    }
}
