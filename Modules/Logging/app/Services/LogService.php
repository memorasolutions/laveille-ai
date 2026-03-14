<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Logging\Services;

use Spatie\Activitylog\Models\Activity;

class LogService
{
    public function log(string $description, ?string $logName = 'default', ?object $subject = null, array $properties = []): Activity|\Spatie\Activitylog\Contracts\Activity|null
    {
        $logger = activity($logName)->withProperties($properties);

        if (auth()->check()) {
            $logger->causedBy(auth()->user());
        }

        if ($subject !== null) {
            $logger->performedOn($subject);
        }

        return $logger->log($description);
    }

    public function getLatest(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return Activity::with(['causer', 'subject'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getByLogName(string $logName, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return Activity::where('log_name', $logName)
            ->with(['causer', 'subject'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getByCauser(object $causer, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return Activity::causedBy($causer)
            ->with(['subject'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getBySubject(object $subject, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return Activity::forSubject($subject)
            ->with(['causer'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function clean(int $daysOld = 90): int
    {
        return Activity::where('created_at', '<', now()->subDays($daysOld))->delete();
    }
}
