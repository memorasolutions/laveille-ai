<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * xAPI léger (couche d'abstraction actor-verb-object, standard 1EdTech/ADL) —
 * dette technique F16 (voir config/version.php). NE DUPLIQUE AUCUNE DONNÉE
 * MÉTIER : chaque ligne est un REFORMATTAGE d'un événement pédagogique déjà
 * enregistré ailleurs (academy_completions, academy_quiz_attempts,
 * academy_xp_events, academy_srs_cards, academy_live_session_attendance).
 * Table append-only, écrite EXCLUSIVEMENT par XapiRecorderService::record().
 * Migration ADDITIVE (guard hasTable), down() = drop strict.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_xapi_statements')) {
            return;
        }

        Schema::create('academy_xapi_statements', function (Blueprint $table): void {
            $table->id();

            // Actor — l'apprenant qui a posé l'action.
            $table->unsignedBigInteger('user_id');

            // Verb — vocabulaire contrôlé court (completed, attempted, answered,
            // attended, reviewed, earned...). Documenté dans XapiRecorderService.
            $table->string('verb', 40);

            // Object — type polymorphe LÉGER (pas de morphs Eloquent : jamais de FK
            // réelle vers un autre module, seulement une étiquette + id), même
            // convention que academy_xp_events.source_type/source_id.
            $table->string('object_type', 40);
            $table->unsignedBigInteger('object_id');

            // Result — score, succès/échec, durée... (nullable : tous les verbes
            // n'ont pas de résultat, ex. « attended »).
            $table->json('result')->nullable();

            // Context — course_id parent, cohort_id, etc. (nullable).
            $table->json('context')->nullable();

            // Snapshot brut de l'événement source (audit/rejeu), jamais affiché tel
            // quel à l'apprenant.
            $table->json('raw_payload');

            // Horodatage pédagogique réel de l'action (peut différer de created_at
            // en cas de rejeu/import) et horodatage d'écriture de la ligne.
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            // Lectures attendues : historique d'un apprenant filtré par verbe/type,
            // et « tous les statements sur tel objet » (ex. graphe de compétences).
            $table->index(['user_id', 'verb', 'object_type'], 'academy_xapi_user_verb_object_idx');
            $table->index(['object_type', 'object_id'], 'academy_xapi_object_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_xapi_statements');
    }
};
