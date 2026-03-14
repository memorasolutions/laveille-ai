<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @author MEMORA solutions <contact@memora.pro>
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('ecommerce_product_attributes')->cascadeOnDelete();
            $table->string('value');
            $table->string('label');
            $table->string('color_code')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_product_attribute_values');
    }
};
