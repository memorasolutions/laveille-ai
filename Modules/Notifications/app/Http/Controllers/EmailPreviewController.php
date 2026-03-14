<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Notifications\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewResponse;

class EmailPreviewController extends Controller
{
    private const PREVIEWS = [
        'welcome' => [
            'name' => 'Courriel de bienvenue',
            'description' => 'Envoyé aux nouveaux utilisateurs après inscription.',
        ],
        'digest' => [
            'name' => 'Résumé hebdomadaire',
            'description' => 'Résumé des activités et mises à jour de la semaine.',
        ],
        'contact' => [
            'name' => 'Message de contact',
            'description' => 'Réponse au formulaire de contact du site.',
        ],
    ];

    public function index(): ViewResponse
    {
        $notifications = self::PREVIEWS;

        return view('notifications::email-preview.index', compact('notifications'));
    }

    public function preview(string $type): Response
    {
        $method = 'preview'.ucfirst($type);

        if (! method_exists($this, $method) || ! array_key_exists($type, self::PREVIEWS)) {
            abort(404);
        }

        $html = $this->$method();

        return response($html)->header('Content-Type', 'text/html');
    }

    protected function previewWelcome(): string
    {
        $user = User::factory()->make(['name' => 'Jean Dupont', 'email' => 'jean@exemple.com']);

        return view('emails.welcome', [
            'user' => $user,
            'url' => url('/dashboard'),
        ])->render();
    }

    protected function previewDigest(): string
    {
        $viewName = 'notifications::email.digest';

        if (! View::exists($viewName)) {
            return '<html><body><h1>Template digest non disponible</h1><p>Le fichier de vue n\'existe pas encore.</p></body></html>';
        }

        return view($viewName, [
            'user' => User::factory()->make(['name' => 'Marie Martin']),
            'articles' => collect([
                (object) ['title' => 'Article exemple 1', 'excerpt' => 'Résumé de l\'article...', 'url' => '#'],
                (object) ['title' => 'Article exemple 2', 'excerpt' => 'Un autre résumé...', 'url' => '#'],
            ]),
        ])->render();
    }

    protected function previewContact(): string
    {
        return view('emails.contact', [
            'data' => [
                'name' => 'Alexandre Tremblay',
                'email' => 'alex@exemple.com',
                'subject' => 'Question sur le plan entreprise',
                'message' => 'Bonjour, j\'aimerais en savoir plus sur vos forfaits entreprise et les fonctionnalités avancées disponibles.',
            ],
        ])->render();
    }
}
