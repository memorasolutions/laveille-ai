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
        Schema::create('ecommerce_related_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('ecommerce_products')->cascadeOnDelete();
            $table->foreignId('related_product_id')->constrained('ecommerce_products')->cascadeOnDelete();
            $table->enum('type', ['cross_sell', 'up_sell'])->default('cross_sell');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'related_product_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_related_products');
    }
};
