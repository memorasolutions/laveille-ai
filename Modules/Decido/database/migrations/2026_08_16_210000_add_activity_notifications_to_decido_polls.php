<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LOT 5 (docs/specs/2026-08-16-decido-reste-a-faire.md) : notification à l'organisateur quand son
 * sondage reçoit de l'activité (vote, déclin, commentaire). Deux colonnes additives :
 *
 * - activity_notifications_enabled : interrupteur PAR SONDAGE (default true, jamais imposé) - un
 *   organisateur qui ne veut plus être dérangé sur CE sondage précis le coupe sans toucher aux
 *   autres. Choisi plutôt qu'un réglage global : deux sondages du même créateur peuvent avoir des
 *   enjeux très différents (le webinaire de cette semaine vs un vieux sondage oublié).
 * - activity_notified_at : horodatage du dernier résumé envoyé, nullable. Sert de curseur "depuis
 *   quand chercher du nouveau" à decido:notify-poll-activity - même rôle qu'expiry_warned_at pour
 *   WarnExpiringPollsCommand, mais réécrit à CHAQUE envoi (jamais une seule fois) puisque le
 *   résumé est censé se répéter tant qu'il y a du nouveau, contrairement à l'avertissement
 *   d'expiration qui n'est envoyé qu'une seule fois par échéance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decido_polls', function (Blueprint $table) {
            if (! Schema::hasColumn('decido_polls', 'activity_notifications_enabled')) {
                $table->boolean('activity_notifications_enabled')->default(true)->after('expiry_warned_at');
            }

            if (! Schema::hasColumn('decido_polls', 'activity_notified_at')) {
                $table->timestamp('activity_notified_at')->nullable()->after('activity_notifications_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('decido_polls', function (Blueprint $table) {
            if (Schema::hasColumn('decido_polls', 'activity_notified_at')) {
                $table->dropColumn('activity_notified_at');
            }

            if (Schema::hasColumn('decido_polls', 'activity_notifications_enabled')) {
                $table->dropColumn('activity_notifications_enabled');
            }
        });
    }
};
