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

        // 2026-08-23 : ces deux boutons exécutaient `config:cache`, la seule commande formellement
        // interdite sur ce projet - elle a silencieusement REFERMÉ l'Académie en production (tout
        // env() devient null une fois la config mise en cache, et le middleware
        // AcademyUnderConstruction ne lisait plus ACADEMY_UNDER_CONSTRUCTION du .env).
        //  - « OptimizedApp » lançait `optimize`, qui appelle `config:cache` EN INTERNE. Or la CI
        //    laisse volontairement ce voyant partiellement rouge : le panneau invitait donc
        //    l'admin à cliquer sur le bouton qui casse le site. Remplacé par les seuls caches
        //    sûrs, exactement ceux de .github/workflows/deploy.yml (aucun ne dépend d'env()).
        //  - « DebugMode » lançait `config:cache`, qui ne corrige même PAS le mode debug (il ne
        //    fait que figer la configuration courante). Un bouton qui ne répare pas ce qu'il
        //    annonce et casse autre chose vaut mieux absent : APP_DEBUG se change dans le .env.
        return match ($check) {
            'OptimizedApp' => $this->runFixes(
                ['route:cache', 'event:cache', 'view:cache'],
                'Caches route, événements et vues reconstruits. La configuration reste lue en direct depuis le .env, volontairement.'
            ),
            'Cache' => $this->runFix('cache:clear', 'Cache vidé avec succès.'),
            'DebugMode' => response()->json([
                'success' => false,
                'message' => "Le mode debug se désactive en mettant APP_DEBUG=false dans le fichier .env, puis en vidant le cache. Aucune commande ne peut le faire à votre place sans risque.",
            ]),
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
        return $this->runFixes([$command], $successMessage);
    }

    /**
     * Exécute plusieurs commandes à la suite et s'arrête à la première qui échoue, en nommant
     * laquelle : un « Erreur : ... » sans le nom de la commande ne dit pas où ça a cassé.
     *
     * @param  array<int, string>  $commands
     */
    private function runFixes(array $commands, string $successMessage): JsonResponse
    {
        foreach ($commands as $command) {
            try {
                Artisan::call($command);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur pendant '.$command.' : '.$e->getMessage(),
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => $successMessage]);
    }
}
