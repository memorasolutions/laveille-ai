<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Enregistrement d'un outil pédagogique externe (LTI 1.3 Tool Provider)
 * branché à l'Académie. Academy est le CONSUMER : ce modèle ne décrit que
 * les paramètres nécessaires à l'échange OIDC/LTI côté outil tiers.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property string $name
 * @property string $issuer
 * @property string $client_id
 * @property string $deployment_id
 * @property string $auth_login_url
 * @property string $auth_token_url
 * @property string $jwks_url
 * @property bool   $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class LtiToolRegistration extends Model
{
    protected $table = 'academy_lti_tool_registrations';

    protected $fillable = [
        'name',
        'issuer',
        'client_id',
        'deployment_id',
        'auth_login_url',
        'auth_token_url',
        'jwks_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
