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
        Schema::table('ecommerce_product_variants', function (Blueprint $table) {
            $table->boolean('allow_backorder')->default(false)->after('low_stock_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_product_variants', function (Blueprint $table) {
            $table->dropColumn('allow_backorder');
        });
    }
};
