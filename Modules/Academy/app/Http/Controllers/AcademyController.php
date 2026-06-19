<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Academy\Models\Course;

class AcademyController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::published();

        $currentFilter = $request->input('filter');
        $currentLevel  = $request->input('level');

        if ($currentFilter === 'free') {
            $query->where('access_type', 'free');
        } elseif ($currentFilter === 'paid') {
            $query->where('access_type', '!=', 'free');
        }

        if ($currentLevel) {
            $query->where('level', $currentLevel);
        }

        $courses = $query->orderBy('published_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        return view('academy::public.index', compact('courses', 'currentFilter', 'currentLevel'));
    }
}
