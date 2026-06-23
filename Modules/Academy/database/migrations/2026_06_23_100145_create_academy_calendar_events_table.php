<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Calendrier d'echéances V5-b. Stocke les événements MANUELS créés par le
 * formateur. Les echéances dérivées des devoirs (Assignment.due_at) sont
 * calculées à la volée par CalendarService, jamais dupliquées ici.
 * Migration ADDITIVE guardée hasTable + hasColumn.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_calendar_events')) {
            return;
        }

        Schema::create('academy_calendar_events', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('lesson_item_id')->nullable();

            $table->string('title');
            $table->text('description')->nullable();

            // Liste blanche: due | exam | live | manual
            $table->string('type')->default('manual');

            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();

            $table->unsignedBigInteger('created_by');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');

            $table->foreign('lesson_item_id')
                ->references('id')
                ->on('lesson_items')
                ->nullOnDelete();

            $table->index(['course_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_calendar_events');
    }
};
