<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Editor\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait for Livewire 3 components to enable autosave.
 *
 * The component must implement getAutosaveModel(): ?Model
 * and getAutosaveData(): array.
 */
trait HasAutosave
{
    public bool $autosaveEnabled = true;

    public int $autosaveInterval = 60;

    public ?string $lastAutosavedAt = null;

    public function autosave(): void
    {
        if (! $this->autosaveEnabled) {
            return;
        }

        $model = $this->getAutosaveModel();

        if (! $model instanceof Model || ! $model->exists) {
            return;
        }

        $data = $this->getAutosaveData();

        if (empty($data)) {
            return;
        }

        $model->fill($data);

        if (empty($model->getDirty())) {
            return;
        }

        $model->save();
        $this->lastAutosavedAt = now()->toDateTimeString();
        $this->dispatch('autosaved');
    }
}
