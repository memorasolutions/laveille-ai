<?php

declare(strict_types=1);

namespace Modules\Directory\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Directory\Models\ToolSuggestion;

trait HasSuggestions
{
    public function suggestions(): MorphMany
    {
        return $this->morphMany(ToolSuggestion::class, 'suggestable');
    }

    public function suggestableFields(): array
    {
        return property_exists($this, 'suggestableFields') ? $this->suggestableFields : [];
    }

    public function suggestableFieldValidation(): string
    {
        return 'in:'.implode(',', array_keys($this->suggestableFields()));
    }
}
