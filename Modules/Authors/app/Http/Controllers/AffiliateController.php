<?php

declare(strict_types=1);

namespace Modules\Authors\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Authors\Models\AuthorAffiliateLink;

final class AffiliateController extends Controller
{
    public function go(Request $request, string $slug): RedirectResponse
    {
        $link = AuthorAffiliateLink::where('slug', $slug)->firstOrFail();

        $link->increment('clicks_count');

        Log::channel('daily')->info('affiliate.click', [
            'slug' => $slug,
            'destination' => $link->destination_url,
            'author_profile_id' => $link->author_profile_id,
            'ip' => $request->ip(),
            'ua' => substr((string) $request->userAgent(), 0, 200),
        ]);

        return redirect()->away($link->destination_url, 302);
    }
}
