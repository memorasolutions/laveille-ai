<?php

declare(strict_types=1);

namespace Modules\Notifications\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AutomationAlertService
{
    public static function fire(string $source, string $title, string $message, array $context = []): void
    {
        try {
            $cacheKey = 'automation_alert:' . md5($source . ':' . $title);

            if (Cache::has($cacheKey)) {
                return;
            }

            $admin = config('app.superadmin_email');

            if (empty($admin)) {
                Log::warning('[AutomationAlertService] superadmin_email non configuré, alerte ignorée.', [
                    'source' => $source,
                    'title' => $title,
                ]);
                return;
            }

            $subject = "[laveille.ai] ALERTE: {$title}";

            $body = implode("\n", [
                '========================================',
                ' ALERTE AUTOMATION',
                '========================================',
                '',
                'Date (UTC) : ' . now()->utc()->toDateTimeString(),
                'Source     : ' . $source,
                'Titre      : ' . $title,
                '',
                '--- Message ---',
                $message,
                '',
                '--- Contexte ---',
                json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                '',
                '========================================',
            ]);

            Mail::raw($body, fn ($m) => $m->to($admin)->subject($subject));

            Cache::put($cacheKey, true, now()->addMinutes(15));
        } catch (\Throwable $e) {
            Log::error('[AutomationAlertService] Impossible d\'envoyer l\'alerte.', [
                'source' => $source,
                'title' => $title,
                'message' => $message,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
