<?php

declare(strict_types=1);

/**
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('type', 50);
            $table->decimal('value', 10, 2)->nullable();
            $table->json('conditions')->nullable();
            $table->json('tiers')->nullable();
            $table->json('bogo_config')->nullable();
            $table->string('applies_to', 50)->default('all');
            $table->json('target_ids')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_stackable')->default(false);
            $table->boolean('is_automatic')->default(true);
            $table->integer('max_uses')->nullable();
            $table->integer('used_count')->default(0);
            $table->datetime('starts_at')->nullable();
            $table->datetime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('starts_at');
            $table->index('expires_at');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_promotions');
    }
};
