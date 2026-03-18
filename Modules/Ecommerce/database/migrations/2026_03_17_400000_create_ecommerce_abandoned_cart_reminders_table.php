<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_abandoned_cart_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('ecommerce_carts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('reminder_number');
            $table->timestamp('sent_at');
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->timestamps();

            $table->unique(['cart_id', 'reminder_number'], 'ecom_cart_reminder_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_abandoned_cart_reminders');
    }
};
