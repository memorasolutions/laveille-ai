<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\CustomFields\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Modules\CustomFields\Models\CustomFieldDefinition;
use Modules\CustomFields\Models\CustomFieldValue;

trait HasCustomFields
{
    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'fieldable');
    }

    public function getCustomField(string $key): mixed
    {
        $definition = CustomFieldDefinition::active()
            ->forModel(static::getModelType())
            ->where('key', $key)
            ->first();

        if (! $definition) {
            return null;
        }

        $fieldValue = $this->customFieldValues()
            ->where('custom_field_definition_id', $definition->id)
            ->first();

        return $fieldValue ? $fieldValue->getCastedValue() : $definition->default_value;
    }

    public function setCustomField(string $key, mixed $value): void
    {
        $definition = CustomFieldDefinition::active()
            ->forModel(static::getModelType())
            ->where('key', $key)
            ->first();

        if (! $definition) {
            return;
        }

        $storedValue = match ($definition->type) {
            'checkbox' => $value ? '1' : '0',
            'date' => $value instanceof \DateTime ? $value->format('Y-m-d') : (string) $value,
            'repeater' => is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : (is_string($value) ? $value : '[]'),
            default => is_null($value) ? null : (string) $value,
        };

        $this->customFieldValues()->updateOrCreate(
            ['custom_field_definition_id' => $definition->id],
            ['value' => $storedValue]
        );
    }

    /** @return array<string, mixed> */
    public function getCustomFields(): array
    {
        $fields = [];
        $definitions = $this->customFieldDefinitions();
        $values = $this->customFieldValues()->with('definition')->get()->keyBy('custom_field_definition_id');

        foreach ($definitions as $definition) {
            $fieldValue = $values->get($definition->id);
            $fields[$definition->key] = $fieldValue ? $fieldValue->getCastedValue() : $definition->default_value;
        }

        return $fields;
    }

    public function saveCustomFields(array $fields): void
    {
        $definitions = CustomFieldDefinition::active()
            ->forModel(static::getModelType())
            ->get()
            ->keyBy('key');

        foreach ($fields as $key => $value) {
            if ($definitions->has($key)) {
                $this->setCustomField($key, $value);
            }
        }
    }

    public static function getModelType(): string
    {
        return strtolower(class_basename(static::class));
    }

    public function customFieldDefinitions(): Collection
    {
        return CustomFieldDefinition::active()
            ->forModel(static::getModelType())
            ->orderBy('sort_order')
            ->get();
    }
}
