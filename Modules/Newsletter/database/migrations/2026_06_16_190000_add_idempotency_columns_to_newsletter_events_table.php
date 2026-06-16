<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration ADDITIVE : ajoute les colonnes d'idempotence et la date réelle de
 * l'événement Brevo à la table `newsletter_events`.
 *
 * - event_key : clé d'idempotence (sha1 stable) + index UNIQUE → un webhook Brevo
 *   rejoué ne crée jamais de doublon.
 * - occurred_at : horodatage réel de l'événement côté Brevo (distinct de created_at,
 *   qui reste la date d'insertion en base).
 *
 * Aucune donnée existante n'est touchée (colonnes nullables, ajout pur).
 * Le down() retire l'index puis les 2 colonnes (réversible).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_events', function (Blueprint $table) {
            // Clé d'idempotence (sha1 = 40 caractères ; 191 = marge sûre + compatible utf8mb4 index).
            if (! Schema::hasColumn('newsletter_events', 'event_key')) {
                $table->string('event_key', 191)
                    ->nullable()
                    ->after('metadata');
            }

            // Date réelle de l'événement Brevo.
            if (! Schema::hasColumn('newsletter_events', 'occurred_at')) {
                $table->timestamp('occurred_at')
                    ->nullable()
                    ->after('event_key');
            }
        });

        // Index UNIQUE sur event_key (ajouté séparément pour rester défensif).
        Schema::table('newsletter_events', function (Blueprint $table) {
            $table->unique('event_key', 'newsletter_events_event_key_unique');
        });
    }

    public function down(): void
    {
        // Retire d'abord l'index UNIQUE, puis les colonnes (ordre inverse de up()).
        Schema::table('newsletter_events', function (Blueprint $table) {
            $table->dropUnique('newsletter_events_event_key_unique');
        });

        Schema::table('newsletter_events', function (Blueprint $table) {
            if (Schema::hasColumn('newsletter_events', 'occurred_at')) {
                $table->dropColumn('occurred_at');
            }

            if (Schema::hasColumn('newsletter_events', 'event_key')) {
                $table->dropColumn('event_key');
            }
        });
    }
};
