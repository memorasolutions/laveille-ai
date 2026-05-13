<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cookie;
use Modules\Tools\Models\QuestProgress;
use Modules\Tools\Services\QuestMagicLinkService;

class QuestController extends Controller
{
    public function index(Request $request)
    {
        if (! config('tools.quest.enabled', false)) {
            abort(404);
        }

        $email = $this->currentEmail($request);
        $progress = $email ? QuestProgress::firstOrCreate(['user_email' => $email]) : null;

        return view('tools::quest.index', [
            'meta' => config('tools.quest.meta'),
            'chapters' => config('tools.quest.chapters'),
            'badges' => config('tools.quest.badges'),
            'email' => $email,
            'isSiteAuth' => auth()->check(),
            'progress' => $progress,
            'completedSlugs' => $progress?->completed_chapters ?? [],
            'currentChapterSlug' => $progress?->current_chapter ?? 'ch1-eveil-octopus',
        ]);
    }

    public function chapter(Request $request, string $slug)
    {
        if (! config('tools.quest.enabled', false)) {
            abort(404);
        }

        $chapter = config("tools.quest.chapters.{$slug}");
        if (! $chapter) {
            abort(404);
        }

        $email = $this->currentEmail($request);
        $progress = $email ? QuestProgress::firstOrCreate(['user_email' => $email]) : null;

        return view('tools::quest.chapter', [
            'meta' => config('tools.quest.meta'),
            'chapter' => $chapter,
            'badges' => config('tools.quest.badges'),
            'email' => $email,
            'progress' => $progress,
        ]);
    }

    public function complete(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'choices' => 'sometimes|array',
            'badge_earned' => 'sometimes|string',
        ]);

        $email = $this->currentEmail($request);
        if (! $email) {
            return response()->json(['ok' => true, 'persisted' => false]);
        }

        $progress = QuestProgress::firstOrCreate(['user_email' => $email]);
        $completed = $progress->completed_chapters ?? [];
        if (! in_array($slug, $completed, true)) {
            $completed[] = $slug;
        }
        $progress->completed_chapters = $completed;

        if (! empty($validated['choices'])) {
            $all = $progress->choices ?? [];
            $all[$slug] = $validated['choices'];
            $progress->choices = $all;
        }

        if (! empty($validated['badge_earned'])) {
            $badges = $progress->badges ?? [];
            if (! in_array($validated['badge_earned'], $badges, true)) {
                $badges[] = $validated['badge_earned'];
            }
            $progress->badges = $badges;
        }

        $progress->last_active_date = now()->toDateString();
        $progress->save();

        return response()->json(['ok' => true, 'persisted' => true, 'badges' => $progress->badges]);
    }

    public function loginRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email:rfc',
        ]);

        $service = app(QuestMagicLinkService::class);
        $sent = $service->generateAndSend($validated['email'], $request->ip());

        return response()->json([
            'ok' => $sent,
            'message' => $sent
                ? 'Lien envoyé. Vérifiez votre boîte courriel (et les spams).'
                : 'Échec d\'envoi. Réessayez dans quelques minutes.',
        ]);
    }

    public function authVerify(Request $request, string $token): RedirectResponse
    {
        $service = app(QuestMagicLinkService::class);
        $email = $service->consume($token);

        if (! $email) {
            return redirect()->route('tools.quest.index')->with('quest_error', 'Lien invalide ou expiré.');
        }

        QuestProgress::firstOrCreate(['user_email' => $email]);

        Cookie::queue(Cookie::make('quest_email', $email, 60 * 24 * 30, '/', null, true, true, false, 'lax'));

        return redirect()->route('tools.quest.index')->with('quest_success', 'Connecté avec succès ! Bienvenue dans la quête.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Cookie::queue(Cookie::forget('quest_email'));

        return redirect()->route('tools.quest.index');
    }

    private function currentEmail(Request $request): ?string
    {
        // #187 : priorise l'utilisateur Laravel authentifié (cohérence UX site-wide).
        // Fallback sur le cookie quest_email (magic-link) pour les visiteurs non-inscrits au site.
        if (auth()->check() && auth()->user()->email) {
            return auth()->user()->email;
        }

        return $request->cookie('quest_email') ?: null;
    }
}
