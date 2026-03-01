<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Modules\Auth\Rules\PasswordPolicyRule;

trait UserRules
{
    protected function baseRules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'email' => ['string', 'email', 'max:255'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }

    protected function passwordRules(): array
    {
        return [
            'string',
            'confirmed',
            new PasswordPolicyRule,
        ];
    }
}
