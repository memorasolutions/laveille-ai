<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
}
