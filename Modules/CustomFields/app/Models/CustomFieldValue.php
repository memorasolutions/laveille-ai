<?php

declare(strict_types=1);

namespace Modules\CustomFields\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class CustomFieldValue extends Model
{
    protected $table = 'custom_field_values';

    /** @var list<string> */
    protected $fillable = [
        'custom_field_definition_id',
        'fieldable_type',
        'fieldable_id',
        'value',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(CustomFieldDefinition::class, 'custom_field_definition_id');
    }

    public function fieldable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getCastedValue(): mixed
    {
        if (is_null($this->value) || ! $this->definition) {
            return $this->value;
        }

        return match ($this->definition->type) {
            'number' => (float) $this->value,
            'checkbox' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'date' => Carbon::parse($this->value),
            default => (string) $this->value,
        };
    }
}
