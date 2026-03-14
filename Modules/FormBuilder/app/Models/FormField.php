<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\FormBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\FormBuilder\Database\Factories\FormFieldFactory;

class FormField extends Model
{
    use HasFactory;

    /** @var list<string> */
    public const TYPES = [
        'text', 'email', 'textarea', 'select', 'checkbox',
        'radio', 'number', 'date', 'file', 'hidden',
    ];

    /** @var list<string> */
    protected $fillable = [
        'form_id',
        'type',
        'label',
        'name',
        'placeholder',
        'options',
        'validation_rules',
        'is_required',
        'sort_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    protected static function newFactory(): FormFieldFactory
    {
        return FormFieldFactory::new();
    }
}
