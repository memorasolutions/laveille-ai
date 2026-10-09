<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

namespace Modules\Idp\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

/**
 * Vue Passport de la table users. Distinct de App\Models\User (qui garde le trait Sanctum)
 * pour éviter toute collision de HasApiTokens.
 */
class OAuthUser extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'users';

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'locked_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** Même définition que App\Models\User::isLocked(). */
    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }
}
