<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Modules\Ecommerce\Models\DigitalAsset;
use Modules\Ecommerce\Models\DigitalAssetDownload;
use Modules\Ecommerce\Models\Order;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DigitalDownloadService
{
    public function generateDownloadUrl(DigitalAsset $asset, Order $order): string
    {
        return URL::temporarySignedRoute(
            'ecommerce.download',
            now()->addHours(24),
            ['asset' => $asset->id, 'order' => $order->id],
        );
    }

    public function processDownload(DigitalAsset $asset, Order $order, User $user, ?string $ip): ?StreamedResponse
    {
        if (! $this->canDownload($asset, $order, $user)) {
            return null;
        }

        DigitalAssetDownload::create([
            'digital_asset_id' => $asset->id,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'downloaded_at' => now(),
            'ip_address' => $ip,
        ]);

        return Storage::download($asset->file_path, $asset->original_filename);
    }

    public function canDownload(DigitalAsset $asset, Order $order, User $user): bool
    {
        if (! $asset->is_active) {
            return false;
        }

        if ((int) $order->user_id !== (int) $user->id) {
            return false;
        }

        if (! in_array($order->status, ['paid', 'delivered'], true)) {
            return false;
        }

        if ($asset->download_limit !== null) {
            $count = DigitalAssetDownload::where('digital_asset_id', $asset->id)
                ->where('order_id', $order->id)
                ->count();

            if ($count >= $asset->download_limit) {
                return false;
            }
        }

        return true;
    }
}
