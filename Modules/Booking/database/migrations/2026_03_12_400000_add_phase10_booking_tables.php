<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_appointments', function (Blueprint $table) {
            $table->unsignedInteger('reschedule_count')->default(0)->after('cancel_reason');
        });

        Schema::create('booking_intake_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('booking_services')->cascadeOnDelete();
            $table->string('label');
            $table->string('type'); // text,textarea,select,checkbox,number,email,phone
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('booking_intake_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('booking_appointments')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('booking_intake_questions')->cascadeOnDelete();
            $table->text('answer')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('secret');
            $table->json('events');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->unsignedSmallInteger('last_status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_webhooks');
        Schema::dropIfExists('booking_intake_answers');
        Schema::dropIfExists('booking_intake_questions');

        Schema::table('booking_appointments', function (Blueprint $table) {
            $table->dropColumn('reschedule_count');
        });
    }
};
