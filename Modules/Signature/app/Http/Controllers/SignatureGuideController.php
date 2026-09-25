<?php

declare(strict_types=1);

namespace Modules\Signature\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * 2 guides au lancement (Gmail web, Outlook web) - décision du fondateur 2026-09-25, section 9.
 */
class SignatureGuideController extends Controller
{
    public function gmail(): View
    {
        return view('signature::public.guide-gmail');
    }

    public function outlookWeb(): View
    {
        return view('signature::public.guide-outlook-web');
    }
}
