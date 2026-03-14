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
        Schema::create('booking_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('session_count');
            $table->decimal('price', 8, 2);
            $table->decimal('regular_price', 8, 2)->nullable();
            $table->unsignedInteger('validity_days')->default(365);
            $table->json('applicable_service_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('booking_package_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('booking_customers')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('booking_packages')->cascadeOnDelete();
            $table->unsignedInteger('sessions_remaining');
            $table->unsignedInteger('sessions_used')->default(0);
            $table->dateTime('purchased_at');
            $table->dateTime('expires_at');
            $table->string('payment_status', 20)->default('pending');
            $table->string('stripe_session_id', 255)->nullable();
            $table->decimal('amount_paid', 8, 2);
            $table->timestamps();
        });

        Schema::create('booking_package_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('booking_package_purchases')->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained('booking_appointments')->cascadeOnDelete();
            $table->dateTime('used_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_package_usages');
        Schema::dropIfExists('booking_package_purchases');
        Schema::dropIfExists('booking_packages');
    }
};
