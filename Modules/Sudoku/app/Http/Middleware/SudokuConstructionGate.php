<?php

declare(strict_types=1);

namespace Modules\Sudoku\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * #166 : bloque acces public a Sudoku en attendant fin de construction.
 * Seul super-admin (stephane@memora.ca par defaut) passe.
 * Visiteurs voient une page construction sobre Memora (pas d'alert natif).
 */
class SudokuConstructionGate
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        // API : 503 JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => 'Outil en construction',
                'message' => 'Le Sudoku quotidien est en cours de finalisation. Revenez bientot.',
            ], 503);
        }

        // Web : vue construction
        return response()->view('sudoku::construction', [], 503);
    }
}
