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
        Schema::create('booking_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->string('type', 20); // percentage or fixed
            $table->decimal('value', 8, 2);
            $table->decimal('min_order_amount', 8, 2)->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('max_uses_per_customer')->default(1);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('new_customers_only')->default(false);
            $table->json('applicable_service_ids')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index(['starts_at', 'expires_at']);
        });

        Schema::create('booking_coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('booking_coupons')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('booking_customers')->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained('booking_appointments')->cascadeOnDelete();
            $table->decimal('discount_amount', 8, 2);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_coupon_usages');
        Schema::dropIfExists('booking_coupons');
    }
};
