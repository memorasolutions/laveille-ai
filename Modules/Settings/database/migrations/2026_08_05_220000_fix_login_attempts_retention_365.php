<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Corrige la valeur EXISTANTE en table settings (90 -> 365 jours) : le seeder ne s'applique
     * qu'aux nouvelles installations (firstOrCreate), la valeur déjà en base doit être mise à
     * jour explicitement. Concordance avec la politique de confidentialité ("12 mois") et
     * privacy:purge-expired qui applique déjà 12 mois (audit 2026-08-05).
     */
    public function up(): void
    {
        DB::table('settings')
            ->where('key', 'retention.login_attempts_days')
            ->where('value', '90')
            ->update(['value' => '365']);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('key', 'retention.login_attempts_days')
            ->where('value', '365')
            ->update(['value' => '90']);
    }
};
