<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Api\Http\Requests;

use Modules\Auth\Rules\PasswordHistoryRule;
use Modules\Auth\Rules\PasswordNotCompromisedRule;
use Modules\Auth\Rules\PasswordPolicyRule;
use Modules\Core\Http\Requests\BaseFormRequest;

class ChangePasswordRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', new PasswordPolicyRule, new PasswordNotCompromisedRule, new PasswordHistoryRule($this->user()?->id)],
        ];
    }
}
