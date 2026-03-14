<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Traits;

trait HasBulkActions
{
    public array $selected = [];

    public bool $selectAll = false;

    public string $bulkAction = '';

    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value ? $this->getBulkPageIds() : [];
    }

    public function executeBulkAction(): void
    {
        if (empty($this->bulkAction) || empty($this->selected)) {
            $this->dispatch('toast', type: 'error', message: __('Sélectionnez des éléments et une action.'));

            return;
        }

        $this->handleBulkAction($this->bulkAction, $this->selected);

        $this->resetBulkSelection();
        $this->dispatch('toast', type: 'success', message: __('Action effectuée.'));
    }

    public function resetBulkSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->bulkAction = '';
    }

    abstract protected function getBulkPageIds(): array;

    abstract protected function handleBulkAction(string $action, array $ids): void;

    abstract protected function getBulkActions(): array;
}
