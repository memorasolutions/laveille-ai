<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\DigitalAsset;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Services\DigitalDownloadService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DigitalDownloadController extends Controller
{
    public function __construct(
        protected DigitalDownloadService $service,
    ) {}

    public function links(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if ((int) $order->user_id !== (int) $user->id) {
            abort(403);
        }

        $links = [];

        foreach ($order->items as $item) {
            $product = $item->variant?->product;
            if (! $product) {
                continue;
            }

            foreach ($product->digitalAssets()->active()->get() as $asset) {
                if ($this->service->canDownload($asset, $order, $user)) {
                    $links[] = [
                        'asset_id' => $asset->id,
                        'filename' => $asset->original_filename,
                        'download_url' => $this->service->generateDownloadUrl($asset, $order),
                    ];
                }
            }
        }

        return response()->json(['data' => $links]);
    }

    public function download(Request $request, DigitalAsset $asset, Order $order): StreamedResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Lien expiré ou invalide.');
        }

        $user = $order->user;

        $response = $this->service->processDownload($asset, $order, $user, $request->ip());

        if (! $response) {
            abort(403, 'Téléchargement non autorisé.');
        }

        return $response;
    }
}
