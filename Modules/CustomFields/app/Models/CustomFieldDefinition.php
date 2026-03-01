<?php

declare(strict_types=1);

namespace Modules\CustomFields\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CustomFieldDefinition extends Model
{
    /** @var list<string> */
    public const TYPES = ['text', 'textarea', 'number', 'date', 'select', 'checkbox', 'radio', 'color', 'url', 'email'];

    /** @var array<string, string> */
    public const MODEL_TYPES = [
        'article' => 'Articles',
        'page' => 'Pages',
    ];

    protected $table = 'custom_field_definitions';

    /** @var list<string> */
    protected $fillable = [
        'name', 'key', 'type', 'options', 'validation_rules',
        'default_value', 'placeholder', 'description',
        'model_type', 'is_required', 'sort_order', 'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->key) && ! empty($model->name)) {
                $model->key = Str::slug($model->name, '_');
            }
        });
    }

    public function customFieldValues(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class, 'custom_field_definition_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForModel(Builder $query, string $modelType): Builder
    {
        return $query->where('model_type', $modelType);
    }

    public function getValidationRule(): string
    {
        $rules = [];

        $rules[] = $this->is_required ? 'required' : 'nullable';

        switch ($this->type) {
            case 'number':
                $rules[] = 'numeric';
                break;
            case 'date':
                $rules[] = 'date';
                break;
            case 'email':
                $rules[] = 'email';
                break;
            case 'url':
                $rules[] = 'url';
                break;
            case 'color':
                $rules[] = 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';
                break;
            case 'select':
            case 'radio':
                if (! empty($this->options) && is_array($this->options)) {
                    $rules[] = 'in:' . implode(',', $this->options);
                }
                break;
            case 'checkbox':
                $rules[] = 'boolean';
                break;
            default:
                $rules[] = 'string';
                break;
        }

        if (! empty($this->validation_rules)) {
            $rules = array_merge($rules, explode('|', $this->validation_rules));
        }

        return implode('|', $rules);
    }
}
