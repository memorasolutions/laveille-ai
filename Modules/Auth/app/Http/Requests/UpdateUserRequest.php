<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\BaseFormRequest;

class UpdateUserRequest extends BaseFormRequest
{
    use UserRules;

    public function rules(): array
    {
        $rules = $this->baseRules();

        return [
            'name' => ['sometimes', ...$rules['name']],
            'email' => ['sometimes', ...$rules['email'], Rule::unique('users')->ignore($this->route('user'))],
            'password' => ['sometimes', ...$this->passwordRules($this->route('user')?->id ?? $this->route('user'))],
            'roles' => $rules['roles'],
            'roles.*' => $rules['roles.*'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }
}
