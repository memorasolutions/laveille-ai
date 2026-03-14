<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Traits;

/**
 * Provides column sorting for Livewire table components.
 *
 * Classes using this trait MUST declare:
 *   public string $sortBy = 'column_name';
 *   public string $sortDirection = 'asc'|'desc';
 */
trait HasTableSorting
{
    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }
}
