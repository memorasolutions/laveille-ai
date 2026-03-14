<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Spatie\Health\ResultStores\ResultStore;

class BackofficeHealthController extends Controller
{
    public function index(): View
    {
        $results = app(ResultStore::class)->latestResults();

        return view('backoffice::health.index', compact('results'));
    }

    public function refresh(): RedirectResponse
    {
        Artisan::call('health:check');

        return redirect()->route('admin.health')->with('success', 'Vérifications effectuées.');
    }

    public function fix(Request $request): JsonResponse
    {
        $check = $request->input('check', '');

        return match ($check) {
            'OptimizedApp' => $this->runFix('optimize', 'Application optimisée avec succès.'),
            'DebugMode' => $this->runFix('config:cache', 'Configuration mise en cache avec succès.'),
            'Cache' => $this->runFix('cache:clear', 'Cache vidé avec succès.'),
            'Schedule', 'Database', 'UsedDiskSpace', 'Environment' => response()->json([
                'success' => false,
                'message' => 'Cette vérification ne peut pas être corrigée automatiquement.',
            ]),
            default => response()->json(['success' => false, 'message' => 'Vérification inconnue.']),
        };
    }

    public function explain(Request $request): JsonResponse
    {
        $check = $request->input('check', '');

        $explanations = [
            'OptimizedApp' => ['explanation' => "L'application n'est pas optimisée. Les caches de configuration, routes et vues ne sont pas activés. Cela ralentit chaque requête.", 'fixable' => true],
            'DebugMode' => ['explanation' => 'Le mode debug est activé. Les erreurs détaillées sont visibles par tous les visiteurs, ce qui expose des informations sensibles.', 'fixable' => true],
            'Cache' => ['explanation' => "Le système de cache ne fonctionne pas correctement. Les données temporaires ne peuvent pas être stockées, ce qui ralentit l'application.", 'fixable' => true],
            'Schedule' => ['explanation' => 'Le planificateur de tâches ne fonctionne pas. Les sauvegardes automatiques, nettoyages et envois programmés sont suspendus.', 'fixable' => false],
            'Database' => ['explanation' => "La connexion à la base de données a échoué. L'application ne peut pas lire ni écrire de données.", 'fixable' => false],
            'UsedDiskSpace' => ['explanation' => "L'espace disque est insuffisant. Les uploads, sauvegardes et logs pourraient échouer.", 'fixable' => false],
            'Environment' => ['explanation' => "L'environnement n'est pas configuré en production. Certaines optimisations et protections sont désactivées.", 'fixable' => false],
        ];

        return response()->json($explanations[$check] ?? ['explanation' => 'Vérification inconnue.', 'fixable' => false]);
    }

    private function runFix(string $command, string $successMessage): JsonResponse
    {
        try {
            Artisan::call($command);

            return response()->json(['success' => true, 'message' => $successMessage]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erreur : '.$e->getMessage()]);
        }
    }
}
