<?php

declare(strict_types=1);

namespace Modules\SEO\Traits;

trait NotifiesIndexNow
{
    public static function bootNotifiesIndexNow(): void
    {
        static::saved(function ($model) {
            if (! $model->wasRecentlyCreated && ! $model->wasChanged()) {
                return;
            }

            if (array_key_exists('status', $model->getAttributes())) {
                if ($model->getAttribute('status') !== 'published') {
                    return;
                }
            } elseif (array_key_exists('is_published', $model->getAttributes())) {
                if (! $model->getAttribute('is_published')) {
                    return;
                }
            }

            if (! method_exists($model, 'getPublicUrl')) {
                return;
            }

            $url = $model->getPublicUrl();

            if (! $url || ! is_string($url)) {
                return;
            }

            if (! class_exists(\Modules\SEO\Services\IndexNowService::class)) {
                return;
            }

            dispatch(function () use ($url) {
                \Modules\SEO\Services\IndexNowService::submit($url);
            })->afterResponse();
        });
    }
}
