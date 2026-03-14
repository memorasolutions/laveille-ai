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
            $table->string('recurrence_type', 20)->default('none');
            $table->date('recurrence_end_date')->nullable();
            $table->unsignedBigInteger('recurrence_parent_id')->nullable();
            $table->foreign('recurrence_parent_id')
                ->references('id')
                ->on('booking_appointments')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('booking_appointments', function (Blueprint $table) {
            $table->dropForeign(['recurrence_parent_id']);
            $table->dropColumn(['recurrence_type', 'recurrence_end_date', 'recurrence_parent_id']);
        });
    }
};
