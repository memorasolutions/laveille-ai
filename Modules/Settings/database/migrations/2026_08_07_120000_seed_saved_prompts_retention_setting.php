<?php
declare(strict_types=1);
/**
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * ACTION: seed du réglage de rétention retention.saved_prompts_trashed_days (mission #1414,
 *         2026-08-07) - les prompts supprimés par leurs propriétaires (soft delete sur
 *         saved_prompts) n'étaient jamais purgés définitivement.
 * RAISON: insertOrIgnore (comme 2026_07_01_140000_seed_ai_escalation_settings.php) plutôt que le
 *         seeder seul, car ce dernier n'est pas rejoué automatiquement à chaque déploiement -
 *         cette migration garantit que la clé existe en prod comme en local dès `migrate`.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('settings')->insertOrIgnore([
            [
                'group' => 'retention',
                'key' => 'retention.saved_prompts_trashed_days',
                'value' => '30',
                'type' => 'number',
                'description' => 'Prompts supprimés (corbeille) - suppression définitive après N jours',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('key', 'retention.saved_prompts_trashed_days')
            ->delete();
    }
};
