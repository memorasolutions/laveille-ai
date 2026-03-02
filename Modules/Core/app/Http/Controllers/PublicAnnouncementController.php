<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Core\Models\Announcement;

class PublicAnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::published()
            ->orderByDesc('published_at')
            ->paginate(20);

        return view('core::public.announcements.index', compact('announcements'));
    }
}
