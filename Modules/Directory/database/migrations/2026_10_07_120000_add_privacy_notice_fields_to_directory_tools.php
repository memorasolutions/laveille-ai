<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * ACTION: champs « fait vérifié par outil » du module précaution données personnelles (v1.321.0).
 * Distincts de privacy_compliance (étiquette courte de conformité, 100 car.) : ici une phrase
 * factuelle, le lien de la politique et la date de vérification. Tous nullables et optionnels,
 * rien ne s'affiche tant qu'ils sont vides. Réversible : down() retire les trois colonnes.
 * Interrupteur public (réglage runtime, OFF par défaut) amorcé par la même migration.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_tools', function (Blueprint $table): void {
            if (! Schema::hasColumn('directory_tools', 'third_party_ai_note')) {
                $table->text('third_party_ai_note')->nullable();
            }
            if (! Schema::hasColumn('directory_tools', 'privacy_policy_url')) {
                $table->string('privacy_policy_url', 500)->nullable();
            }
            if (! Schema::hasColumn('directory_tools', 'privacy_checked_at')) {
                $table->date('privacy_checked_at')->nullable();
            }
        });

        if (Schema::hasTable('settings')) {
            DB::table('settings')->insertOrIgnore([[
                'group' => 'directory',
                'key' => 'directory.privacy_notice_enabled',
                'value' => '0',
                'type' => 'boolean',
                'description' => 'Annuaire : afficher la note de précaution données personnelles sur les fiches (OFF par défaut)',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);
        }
    }

    public function down(): void
    {
        Schema::table('directory_tools', function (Blueprint $table): void {
            foreach (['third_party_ai_note', 'privacy_policy_url', 'privacy_checked_at'] as $column) {
                if (Schema::hasColumn('directory_tools', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasTable('settings')) {
            DB::table('settings')->where('key', 'directory.privacy_notice_enabled')->delete();
        }
    }
};
