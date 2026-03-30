<?php

declare(strict_types=1);

namespace Modules\Core\Services;

class ContentQualityResult
{
    public function __construct(
        public bool $passed,
        public array $reasons,
        public int $score,
    ) {}
}
