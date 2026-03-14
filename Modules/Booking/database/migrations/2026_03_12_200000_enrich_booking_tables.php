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
        Schema::table('booking_customers', function (Blueprint $table) {
            $table->string('timezone', 50)->default('America/Toronto')->after('phone');
            $table->unsignedInteger('no_show_count')->default(0)->after('notes');
            $table->string('portal_token', 64)->nullable()->unique()->after('no_show_count');
        });

        Schema::table('booking_services', function (Blueprint $table) {
            $table->text('long_description')->nullable()->after('description');
            $table->json('benefits')->nullable()->after('long_description');
            $table->string('image', 255)->nullable()->after('benefits');
            $table->string('category', 100)->nullable()->index()->after('image');
            $table->string('duration_display', 50)->nullable()->after('duration_minutes');
            $table->string('price_display', 50)->nullable()->after('price');
        });

        Schema::table('booking_appointments', function (Blueprint $table) {
            $table->string('payment_status', 20)->nullable()->after('cancel_reason');
            $table->decimal('amount_paid', 8, 2)->nullable()->after('payment_status');
            $table->string('stripe_session_id', 255)->nullable()->unique()->after('amount_paid');
            $table->unsignedBigInteger('coupon_id')->nullable()->after('stripe_session_id');
            $table->decimal('discount_amount', 8, 2)->default(0)->after('coupon_id');
        });

        Schema::table('booking_date_overrides', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->boolean('repeat_yearly')->default(false)->after('end_time');
            $table->string('label', 255)->nullable()->after('repeat_yearly');
        });
    }

    public function down(): void
    {
        Schema::table('booking_customers', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'no_show_count', 'portal_token']);
        });

        Schema::table('booking_services', function (Blueprint $table) {
            $table->dropColumn(['long_description', 'benefits', 'image', 'category', 'duration_display', 'price_display']);
        });

        Schema::table('booking_appointments', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'amount_paid', 'stripe_session_id', 'coupon_id', 'discount_amount']);
        });

        Schema::table('booking_date_overrides', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'repeat_yearly', 'label']);
        });
    }
};
