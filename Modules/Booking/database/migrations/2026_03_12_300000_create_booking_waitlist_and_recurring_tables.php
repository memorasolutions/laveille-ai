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
        Schema::create('booking_waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('booking_customers')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('booking_services')->cascadeOnDelete();
            $table->date('preferred_date')->nullable();
            $table->string('preferred_time_start', 5)->nullable();
            $table->string('preferred_time_end', 5)->nullable();
            $table->string('status', 20)->default('waiting'); // waiting, notified, booked, expired
            $table->dateTime('notified_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_recurring_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('booking_customers')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('booking_services')->cascadeOnDelete();
            $table->string('frequency', 20); // weekly, biweekly, monthly
            $table->tinyInteger('day_of_week')->nullable(); // 0=Sun...6=Sat
            $table->string('preferred_time', 5); // "10:00"
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_generated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_recurring_appointments');
        Schema::dropIfExists('booking_waitlist_entries');
    }
};
