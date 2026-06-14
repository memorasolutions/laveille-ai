<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Modules\Tools\Models\QtAttempt;
use Modules\Tools\Services\QtService;

class PublicQtController extends Controller
{
    /**
     * Jeu « QT — Quotient Techno ».
     * EN CONSTRUCTION : jouable seulement par un superadmin ; le public voit « En construction ».
     */
    public function play(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();

        if (! $isAdmin) {
            return view('tools::public.under-construction', [
                'tool' => (object) ['name' => 'QT — Quotient Techno'],
            ]);
        }

        $tool = \Modules\Tools\Models\Tool::where('slug', 'qt')->first();

        return view('tools::public.qt', [
            'round' => QtService::newRound(),
            'daily' => QtService::dailyRound(),
            'share' => $tool?->getShareData(),
        ]);
    }

    /**
     * Enregistre une partie (aucune PII) et renvoie une preuve sociale honnête :
     * nombre de parties + percentile (seulement si l'échantillon ≥ 20, pour éviter
     * des chiffres trompeurs au démarrage).
     */
    public function attempt(Request $request)
    {
        $validated = $request->validate([
            'qt' => 'required|integer|min:55|max:145',
            'mode' => 'required|in:defi,libre',
            'defi_number' => 'nullable|integer',
            'correct' => 'required|integer|min:0|max:10',
        ]);

        $qt = (int) $validated['qt'];
        $mode = $validated['mode'];
        $defiNumber = isset($validated['defi_number']) ? (int) $validated['defi_number'] : null;

        QtAttempt::create([
            'qt' => $qt,
            'mode' => $mode,
            'defi_number' => $mode === 'defi' ? $defiNumber : null,
            'correct_count' => (int) $validated['correct'],
            'created_at' => now('UTC'),
        ]);

        $query = QtAttempt::where('mode', $mode);
        if ($mode === 'defi') {
            $query->where('defi_number', $defiNumber);
        }

        $total = (clone $query)->count();
        $beats = (clone $query)->where('qt', '<', $qt)->count();
        $percentile = $total >= 20 ? (int) round($beats / $total * 100) : null;

        if ($mode === 'defi') {
            $playersToday = $total;
        } else {
            $playersToday = (clone $query)
                ->whereDate('created_at', Carbon::now('America/Toronto')->toDateString())
                ->count();
        }

        return response()->json([
            'ok' => true,
            'today' => $playersToday,
            'total' => $total,
            'percentile' => $percentile,
        ]);
    }
}
