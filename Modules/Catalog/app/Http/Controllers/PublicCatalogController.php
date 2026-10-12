<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Catalog\Services\MoodleCatalogService;

class PublicCatalogController extends Controller
{
    public function index(): View
    {
        $categories = app(MoodleCatalogService::class)->getCatalogue();

        return view('catalog::public.index', compact('categories'));
    }
}
