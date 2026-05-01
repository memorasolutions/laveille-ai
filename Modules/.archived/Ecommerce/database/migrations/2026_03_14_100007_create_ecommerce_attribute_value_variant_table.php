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
        Schema::create('ecommerce_attribute_value_variant', function (Blueprint $table) {
            $table->foreignId('variant_id')->constrained('ecommerce_product_variants')->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained('ecommerce_product_attribute_values')->cascadeOnDelete();
            $table->primary(['variant_id', 'attribute_value_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_attribute_value_variant');
    }
};
