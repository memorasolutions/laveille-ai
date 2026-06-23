<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * FORUM - SUJETS de discussion attachés à un item de leçon « forum » (type Moodle
 * « Forum »). Table ADDITIVE et idempotente (guards) : aucune autre table n'est
 * touchée, aucune dépendance.
 *
 *   - lesson_item_id : item « forum » porteur (FK cascade).
 *   - user_id NULLABLE (nullOnDelete) : auteur ; null si le compte est supprimé
 *     (le sujet est conservé, affiché « (inconnu) »).
 *   - is_pinned : sujet épinglé (remonté en tête, modération).
 *   - is_locked : sujet verrouillé (lecture seule ; plus de réponse sauf gérant).
 *   - SoftDeletes : un sujet supprimé est conservé (audit/modération), exclu par défaut.
 *   - Index (lesson_item_id, is_pinned) : tri « épinglés d'abord » performant.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_forum_topics')) {
            return;
        }

        Schema::create('academy_forum_topics', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lesson_item_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title', 200);
            $table->text('body');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lesson_item_id')
                ->references('id')
                ->on('lesson_items')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['lesson_item_id', 'is_pinned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_forum_topics');
    }
};
