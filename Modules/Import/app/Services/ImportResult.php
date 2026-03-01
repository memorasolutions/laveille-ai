<?php

declare(strict_types=1);

namespace Modules\Import\Services;

class ImportResult
{
    public int $total = 0;

    public int $imported = 0;

    public int $skipped = 0;

    /** @var array<int, string> */
    public array $errors = [];

    public function isSuccess(): bool
    {
        return $this->skipped === 0 && empty($this->errors);
    }

    public function getSuccessRate(): float
    {
        if ($this->total === 0) {
            return 0.0;
        }

        return ($this->imported / $this->total) * 100;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
            'success_rate' => $this->getSuccessRate(),
            'is_success' => $this->isSuccess(),
        ];
    }
}
