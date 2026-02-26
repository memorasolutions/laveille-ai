<?php

declare(strict_types=1);

namespace Modules\Backoffice\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class FeatureFlagCondition extends Model
{
    public const TYPE_ALWAYS = 'always';

    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_ROLES = 'roles';

    public const TYPE_ENVIRONMENT = 'environment';

    public const TYPE_SCHEDULE = 'schedule';

    protected $table = 'feature_flag_conditions';

    protected $fillable = [
        'feature_name',
        'condition_type',
        'condition_config',
    ];

    protected $casts = [
        'condition_config' => 'array',
    ];

    /**
     * @return array<string, string>
     */
    public static function availableTypes(): array
    {
        return [
            self::TYPE_ALWAYS => 'Toujours actif',
            self::TYPE_PERCENTAGE => 'Pourcentage',
            self::TYPE_ROLES => 'Par rôles',
            self::TYPE_ENVIRONMENT => 'Par environnement',
            self::TYPE_SCHEDULE => 'Par période',
        ];
    }

    public function isActive(?User $user = null): bool
    {
        return match ($this->condition_type) {
            self::TYPE_ALWAYS => true,
            self::TYPE_PERCENTAGE => $this->evaluatePercentage(),
            self::TYPE_ROLES => $this->evaluateRoles($user),
            self::TYPE_ENVIRONMENT => $this->evaluateEnvironment(),
            self::TYPE_SCHEDULE => $this->evaluateSchedule(),
            default => false,
        };
    }

    private function evaluatePercentage(): bool
    {
        $percentage = $this->condition_config['percentage'] ?? 0;

        return random_int(1, 100) <= (int) $percentage;
    }

    private function evaluateRoles(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $roles = $this->condition_config['roles'] ?? [];

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    private function evaluateEnvironment(): bool
    {
        $environments = $this->condition_config['environments'] ?? [];

        return in_array(app()->environment(), $environments, true);
    }

    private function evaluateSchedule(): bool
    {
        $start = $this->condition_config['start_date'] ?? null;
        $end = $this->condition_config['end_date'] ?? null;

        if (! $start || ! $end) {
            return false;
        }

        $now = Carbon::now();

        return $now->between(Carbon::parse($start), Carbon::parse($end));
    }
}
