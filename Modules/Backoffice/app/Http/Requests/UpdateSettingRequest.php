<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:255', 'regex:/^\S+$/', 'unique:settings,key,'.$this->route('setting')->id],
            'value' => ['nullable', 'string'],
            'group' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:string,boolean,integer,json'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_public' => ['nullable'],
        ];
    }
}
