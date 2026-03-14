<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Rules\PasswordNotCompromisedRule;
use Modules\Auth\Rules\PasswordPolicyRule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['nullable', 'confirmed', new PasswordPolicyRule, new PasswordNotCompromisedRule],
            'phone' => ['nullable', 'string', 'max:20'],
            'must_change_password' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
        ];
    }
}
