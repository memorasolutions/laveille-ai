<?php
declare(strict_types=1);
/**
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $settings = [
            [
                'group' => 'ux',
                'key' => 'guest_cta.enabled',
                'value' => '0',
                'type' => 'boolean',
                'description' => 'Active la barre incitation à créer un compte membre (affichée aux visiteurs non connectés après consent cookies)',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'ux',
                'key' => 'guest_cta.variant',
                'value' => 'v1_sticky_bottom',
                'type' => 'text',
                'description' => 'Variante UX : v1_sticky_bottom | v4_hybride (futur)',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'ux',
                'key' => 'guest_cta.dismiss_days',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Durée en jours avant réapparition après fermeture par l’utilisateur',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('settings')->insertOrIgnore($settings);
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', [
                'guest_cta.enabled',
                'guest_cta.variant',
                'guest_cta.dismiss_days',
            ])
            ->delete();
    }
};
