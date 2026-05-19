<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Api\Http\Requests;

use Modules\Auth\Rules\PasswordPolicyRule;
use Modules\Core\Http\Requests\BaseFormRequest;

class RegisterRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // SÉCURITÉ #254 (v1.19.15) : suppression volontaire de `unique:users` sur le champ email.
        //
        // Raison : la règle `unique` faisait fuir l'existence d'un compte (user enumeration).
        // Un attaquant envoyait des emails au hasard et recevait soit "succès" soit
        // "adresse déjà utilisée" → énumération triviale des utilisateurs inscrits.
        //
        // Le doublon est désormais bloqué côté AuthController::register() qui :
        //  - ne crée PAS un second User si l'email existe déjà ;
        //  - envoie un mail RegistrationAttemptMail au compte existant ;
        //  - retourne dans TOUS LES CAS la même réponse 201 générique.
        //
        // Conserver `required + string + email + max:255` pour rejeter les payloads malformés.
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', new PasswordPolicyRule],
        ];
    }
}
