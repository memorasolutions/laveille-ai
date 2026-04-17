<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/** Wrapper DRY autour de Spatie LogsActivity avec conventions projet (FR). */
trait LogsActivityStandard
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        $fields = property_exists($this, 'activitylogFields') ? $this->activitylogFields : ['name'];
        $logName = property_exists($this, 'activitylogName') ? $this->activitylogName : strtolower(class_basename(static::class));

        return LogOptions::defaults()
            ->logOnly($fields)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName($logName)
            ->setDescriptionForEvent(fn (string $eventName): string => $this->describeActivityEvent($eventName));
    }

    protected function describeActivityEvent(string $eventName): string
    {
        $model = class_basename(static::class);

        return match ($eventName) {
            'created' => "{$model} créé",
            'updated' => "{$model} modifié",
            'deleted' => "{$model} supprimé",
            default => $eventName,
        };
    }
}
