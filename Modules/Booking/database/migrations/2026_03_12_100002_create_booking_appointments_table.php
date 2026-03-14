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
        Schema::create('booking_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('booking_services');
            $table->foreignId('customer_id')->constrained('booking_customers');
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('status')->default('pending');
            $table->string('google_event_id')->nullable();
            $table->string('google_meet_link')->nullable();
            $table->string('source')->default('web');
            $table->string('cancel_token', 64)->unique()->nullable();
            $table->text('notes')->nullable();
            $table->json('reminders_sent')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();

            $table->index('start_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_appointments');
    }
};
