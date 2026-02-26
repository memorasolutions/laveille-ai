<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

class FaqController extends Controller
{
    public function show(): View
    {
        $faqs = [
            [
                'question' => 'Qu\'est-ce que ce projet Laravel SaaS ?',
                'answer' => 'Il s\'agit d\'un template modulaire construit avec Laravel 12, incluant un blog, un système de contact, une newsletter, une gestion utilisateur et bien plus.',
            ],
            [
                'question' => 'Comment puis-je créer un compte ?',
                'answer' => 'Cliquez sur "Inscription" ou connectez-vous via Google ou GitHub. Vous pouvez aussi utiliser un lien magique sans mot de passe.',
            ],
            [
                'question' => 'Puis-je utiliser ce projet comme boilerplate ?',
                'answer' => 'Oui, ce projet est conçu comme un point de départ. Tous les modules sont indépendants et activables selon vos besoins.',
            ],
            [
                'question' => 'Comment soumettre un bug ou une suggestion ?',
                'answer' => 'Utilisez le formulaire de contact disponible sur la page /contact ou créez une issue sur le dépôt GitHub du projet.',
            ],
            [
                'question' => 'La 2FA est-elle supportée ?',
                'answer' => 'Oui, l\'authentification à deux facteurs (TOTP) est intégrée. Vous pouvez l\'activer depuis votre profil avec Google Authenticator ou tout autre app compatible.',
            ],
            [
                'question' => 'Comment personnaliser le thème admin ?',
                'answer' => 'Le backoffice utilise WowDash (Bootstrap 5). Les couleurs et logos sont personnalisables depuis la page /admin/branding sans modifier le code.',
            ],
        ];

        return view('faq', compact('faqs'));
    }
}
