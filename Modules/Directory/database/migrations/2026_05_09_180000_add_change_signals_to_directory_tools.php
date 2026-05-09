<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * S90 #43 — Signaux de fraîcheur pour fiches outils (best practice 2026 EEAT/AI Overviews).
 *
 * Permet d'afficher des badges "Prix modifié", "Nouvelle API", "Fermé", "Racheté" etc.
 * Le composant Blade fait fallback sur education_last_checked_at / updated_at
 * pour afficher "Vérifié il y a X jours" si aucun signal de changement explicite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            if (!Schema::hasColumn('directory_tools', 'last_change_detected_at')) {
                $table->timestamp('last_change_detected_at')->nullable()->after('education_last_checked_at');
            }
            if (!Schema::hasColumn('directory_tools', 'last_change_type')) {
                // enum-like via varchar : price_changed, new_api, new_feature, closed,
                // acquired, mobile_added, language_added, education_added, free_tier_added,
                // pricing_increased, pricing_decreased, beta_left, deprecated
                $table->string('last_change_type', 32)->nullable()->after('last_change_detected_at');
            }
            if (!Schema::hasColumn('directory_tools', 'last_change_note')) {
                $table->string('last_change_note', 255)->nullable()->after('last_change_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->dropColumn(['last_change_detected_at', 'last_change_type', 'last_change_note']);
        });
    }
};
