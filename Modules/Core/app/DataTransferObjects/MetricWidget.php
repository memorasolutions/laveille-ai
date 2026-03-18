<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\DataTransferObjects;

readonly class MetricWidget
{
    /** @param 'number'|'currency'|'percent'|'chart' $type */
    public function __construct(
        public string $name,
        public string $value,
        public string $type = 'number',
        public ?string $change = null,
        public ?string $icon = null,
        public ?string $route = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'value' => $this->value,
            'type' => $this->type,
            'change' => $this->change,
            'icon' => $this->icon,
            'route' => $this->route,
        ], fn ($v) => $v !== null);
    }
}
