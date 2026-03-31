<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PrivacyCenterController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $dataCategories = [
            ['name' => __('Profil'), 'description' => __('Nom, courriel, avatar, bio'), 'icon' => 'user'],
            ['name' => __('Articles'), 'description' => __('Articles publiés et brouillons'), 'icon' => 'file-text'],
            ['name' => __('Commentaires'), 'description' => __('Commentaires sur les articles'), 'icon' => 'message-circle'],
            ['name' => __('Sessions'), 'description' => __('Sessions de connexion actives'), 'icon' => 'monitor'],
            ['name' => __('Historique de connexion'), 'description' => __('Tentatives de connexion récentes'), 'icon' => 'log-in'],
            ['name' => __('Abonnement'), 'description' => __('Plan et facturation Stripe'), 'icon' => 'credit-card'],
            ['name' => __('Conversations IA'), 'description' => __('Historique des conversations chatbot'), 'icon' => 'bot'],
        ];

        return view('auth::themes.backend.privacy.index', [
            'user' => $user,
            'dataCategories' => $dataCategories,
        ]);
    }

    public function deleteAccount(Request $request): RedirectResponse
    {
        $request->validate(['confirm_email' => 'required|email']);

        $user = auth()->user();

        if ($request->confirm_email !== $user->email) {
            return back()->withErrors(['confirm_email' => __('Le courriel ne correspond pas a votre compte.')]);
        }

        Log::warning('[AccountDeletion] User #' . $user->id . ' (' . $user->email . ') requested account deletion.');

        // Soft delete related data (modules may not exist)
        try { DB::table('saved_prompts')->where('user_id', $user->id)->update(['deleted_at' => now()]); } catch (\Throwable) {}
        try { DB::table('user_bookmarks')->where('user_id', $user->id)->delete(); } catch (\Throwable) {}
        try { DB::table('newsletter_subscribers')->where('email', $user->email)->update(['unsubscribed_at' => now()]); } catch (\Throwable) {}

        // Anonymize user (don't hard delete — preserve referential integrity)
        $user->update([
            'name' => '[compte supprime]',
            'email' => 'deleted-' . $user->id . '@laveille.ai',
            'avatar' => null,
            'bio' => null,
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', __('Votre compte a ete supprime. Vos donnees seront purgees selon notre politique de retention.'));
    }
}
