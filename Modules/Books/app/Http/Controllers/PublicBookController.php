<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Books\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Modules\Books\Models\Book;

/**
 * Contrôleur public de la bibliothèque de livres (/livres).
 *
 * Squelette : les vues books::public.index / books::public.show sont des
 * placeholders minimaux (seront réécrites dans une étape séparée) — ce
 * contrôleur existe pour que les routes ne 500 pas.
 */
class PublicBookController extends Controller
{
    public function index(): View
    {
        $books = Book::orderBy('sort_order')->get();

        return view('books::public.index', ['books' => $books]);
    }

    public function show(string $slug): View
    {
        $book = Book::where('slug', $slug)->first();

        if (! $book) {
            abort(404);
        }

        return view('books::public.show', ['book' => $book]);
    }
}
