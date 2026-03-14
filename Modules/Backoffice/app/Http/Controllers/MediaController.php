<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController
{
    public function index(): View
    {
        return view('backoffice::media.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $media = Media::findOrFail($id);
        $media->delete();

        return redirect()->route('admin.media.index')
            ->with('success', 'Média supprimé.');
    }
}
