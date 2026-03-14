<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Modules\Core\Http\Requests\BaseFormRequest;

class StoreUserRequest extends BaseFormRequest
{
    use UserRules;

    public function rules(): array
    {
        $rules = $this->baseRules();

        return [
            'name' => ['required', ...$rules['name']],
            'email' => ['required', ...$rules['email'], 'unique:users,email'],
            'password' => ['required', ...$this->passwordRules()],
            'roles' => $rules['roles'],
            'roles.*' => $rules['roles.*'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => "L'adresse email est obligatoire.",
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }
}
