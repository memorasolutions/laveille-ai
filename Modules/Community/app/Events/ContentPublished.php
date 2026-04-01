<?php

declare(strict_types=1);

namespace Modules\Community\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContentPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $categoryTag,
        public string $module,
        public Model $content,
    ) {}
}
