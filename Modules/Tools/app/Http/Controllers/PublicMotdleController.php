<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Tools\Services\MotdleWordService;

class PublicMotdleController extends Controller
{
    /**
     * Affiche la page de jeu Motdle avec le mot du jour.
     */
    public function play()
    {
        $word = MotdleWordService::today();

        return view('tools::public.motdle', ['word' => $word]);
    }
}
