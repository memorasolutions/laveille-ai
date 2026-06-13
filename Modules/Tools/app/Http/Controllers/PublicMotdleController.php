<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tools\Services\MotdleWordService;

class PublicMotdleController extends Controller
{
    /**
     * Affiche le jeu Motdle.
     *
     * En construction : le jeu n'est jouable que par un superadmin pour l'instant ;
     * le public voit la page « En construction ».
     */
    public function play(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();

        if (! $isAdmin) {
            return view('tools::public.under-construction', ['tool' => (object) ['name' => 'Motdle']]);
        }

        $word = MotdleWordService::today();

        return view('tools::public.motdle', ['word' => $word]);
    }
}
