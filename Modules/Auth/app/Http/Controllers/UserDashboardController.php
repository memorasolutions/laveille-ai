<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Comment;
use Modules\Core\Shared\Traits\VerifiesPassword;
use Modules\SaaS\Models\Plan;

class UserDashboardController extends Controller
{
    use VerifiesPassword;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function dashboard(): View
    {
        $user = auth()->user();

        $userArticleIds = Article::where('user_id', $user->id)->pluck('id');

        $stats = [
            'articles_count' => $userArticleIds->count(),
            'published_count' => Article::where('user_id', $user->id)->where('status', 'published')->count(),
            'draft_count' => Article::where('user_id', $user->id)->where('status', 'draft')->count(),
            'comments_count' => Comment::whereIn('article_id', $userArticleIds)->count(),
        ];

        $recentArticles = Article::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // Plan actuel (via subscriptions → plans, ou "Free")
        $planName = 'Free';
        $activeSub = DB::table('subscriptions')
            ->where('user_id', $user->id)
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->whereNull('ends_at')
            ->first();

        if ($activeSub) {
            $plan = Plan::where('stripe_price_id', $activeSub->stripe_price)->first();
            $planName = $plan->name ?? 'Pro';
        }

        $unreadNotifications = $user->unreadNotifications()->count();

        return view('auth::dashboard.index', compact(
            'user', 'stats', 'recentArticles', 'planName', 'unreadNotifications'
        ));
    }

    public function profile(): View
    {
        $user = auth()->user();

        return view('auth::profile.index', compact('user'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'bio' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($validated);

        return back()->with('success', 'Profil mis à jour avec succès.');
    }

    public function deleteAccount(Request $request): RedirectResponse
    {
        if ($failed = $this->verifyPasswordOrFail($request)) {
            return $failed;
        }

        $user = auth()->user();

        Auth::logout();
        $user->tokens()->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $user->delete();

        return redirect('/')->with('success', 'Votre compte a été supprimé.');
    }

    public function exportData(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = auth()->user();

        $data = [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'bio' => $user->bio,
                'avatar' => $user->avatar,
                'created_at' => $user->created_at?->toDateTimeString(),
            ],
            'articles' => Article::where('user_id', $user->id)
                ->get(['id', 'title', 'status', 'created_at'])
                ->toArray(),
            'tokens' => $user->tokens()
                ->get(['name', 'created_at', 'last_used_at'])
                ->toArray(),
            'exported_at' => now()->toDateTimeString(),
        ];

        return response()->streamDownload(
            function () use ($data) {
                echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            },
            'mes-donnees-'.date('Y-m-d').'.json',
            ['Content-Type' => 'application/json']
        );
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect.']);
        }

        $user->update(['password' => $request->password]);

        return back()->with('success', 'Mot de passe modifié avec succès.');
    }
}
