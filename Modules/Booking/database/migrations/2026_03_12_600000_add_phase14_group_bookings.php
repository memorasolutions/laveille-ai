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
        if (! Schema::hasColumn('booking_services', 'max_participants')) {
            Schema::table('booking_services', function (Blueprint $table) {
                $table->unsignedInteger('max_participants')->default(1)->after('description');
            });
        }

        if (! Schema::hasColumn('booking_services', 'is_group')) {
            Schema::table('booking_services', function (Blueprint $table) {
                $table->boolean('is_group')->default(false)->after('max_participants');
            });
        }

        if (! Schema::hasTable('booking_group_registrations')) {
            Schema::create('booking_group_registrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('appointment_id')->constrained('booking_appointments')->onDelete('cascade');
                $table->foreignId('customer_id')->constrained('booking_customers')->onDelete('cascade');
                $table->string('status', 20)->default('registered');
                $table->timestamp('registered_at')->useCurrent();
                $table->timestamps();

                $table->index(['appointment_id', 'status']);
                $table->index(['customer_id', 'status']);
            });
        }

        if (! Schema::hasColumn('booking_customers', 'total_bookings')) {
            Schema::table('booking_customers', function (Blueprint $table) {
                $table->unsignedInteger('total_bookings')->default(0)->after('email');
            });
        }

        if (! Schema::hasColumn('booking_customers', 'total_spent')) {
            Schema::table('booking_customers', function (Blueprint $table) {
                $table->decimal('total_spent', 10, 2)->default(0)->after('total_bookings');
            });
        }

        if (! Schema::hasColumn('booking_customers', 'last_booking_at')) {
            Schema::table('booking_customers', function (Blueprint $table) {
                $table->timestamp('last_booking_at')->nullable()->after('total_spent');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_group_registrations');

        Schema::table('booking_customers', function (Blueprint $table) {
            $table->dropColumn(['last_booking_at', 'total_spent', 'total_bookings']);
        });

        Schema::table('booking_services', function (Blueprint $table) {
            $table->dropColumn(['is_group', 'max_participants']);
        });
    }
};
