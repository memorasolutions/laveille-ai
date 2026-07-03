<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Services;

use App\Models\User;
use Modules\Sso\Models\SsoConfiguration;

/**
 * Journal d'audit du provisioning SCIM — RÉUTILISE spatie/laravel-activitylog
 * (déjà dépendance du projet, déjà affiché dans /admin/activity-logs via
 * Modules\Backoffice\Http\Controllers\ActivityLogController). AUCUNE nouvelle
 * table créée (règle DRY) : chaque événement est journalisé avec
 * log_name="sso-provisioning", causer = l'organisation (subject), sujet =
 * l'utilisateur provisionné.
 */
class ScimAuditService
{
    public function record(SsoConfiguration $configuration, User $user, string $action, array $properties = []): void
    {
        if (! class_exists(\Spatie\Activitylog\Facades\Activity::class) && ! function_exists('activity')) {
            return;
        }

        activity('sso-provisioning')
            ->performedOn($user)
            ->withProperties(array_merge($properties, [
                'organization_slug' => $configuration->organization_slug,
                'sso_configuration_id' => $configuration->getKey(),
            ]))
            ->log($this->describe($action, $configuration, $user));
    }

    private function describe(string $action, SsoConfiguration $configuration, User $user): string
    {
        return match ($action) {
            'created' => "SCIM : utilisateur {$user->email} provisionné par {$configuration->organization_slug}",
            'updated' => "SCIM : utilisateur {$user->email} mis à jour par {$configuration->organization_slug}",
            'deactivated' => "SCIM : utilisateur {$user->email} désactivé par {$configuration->organization_slug}",
            'reactivated' => "SCIM : utilisateur {$user->email} réactivé par {$configuration->organization_slug}",
            default => "SCIM : action « {$action} » sur {$user->email} par {$configuration->organization_slug}",
        };
    }
}
