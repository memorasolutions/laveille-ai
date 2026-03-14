<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Routing\Controller;
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
}
