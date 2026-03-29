<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\FrontTheme\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FrontThemeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('fronttheme::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('fronttheme::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('fronttheme::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('fronttheme::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
