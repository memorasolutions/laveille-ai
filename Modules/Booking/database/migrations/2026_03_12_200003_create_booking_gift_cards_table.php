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
        Schema::create('booking_gift_cards', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('purchaser_name');
            $table->string('purchaser_email');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable();
            $table->text('recipient_message')->nullable();
            $table->decimal('initial_amount', 8, 2);
            $table->decimal('remaining_amount', 8, 2);
            $table->string('currency', 3)->default('CAD');
            $table->string('status', 20)->default('active'); // active, expired, exhausted
            $table->dateTime('purchased_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_gift_card_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_card_id')->constrained('booking_gift_cards')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('booking_appointments')->nullOnDelete();
            $table->decimal('amount_used', 8, 2);
            $table->dateTime('used_at');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_gift_card_usages');
        Schema::dropIfExists('booking_gift_cards');
    }
};
