<?php

declare(strict_types=1);

namespace Modules\Community\Traits;

use Modules\Community\Models\Report;

trait HasReports
{
    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}
