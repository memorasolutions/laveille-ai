<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Additive : nouvelle table, aucune table existante modifiée. Inerte tant que le drapeau est OFF.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_print_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('shop_products')->onDelete('restrict');
            $table->string('variant_uid')->nullable();          // null = tout le produit
            $table->string('print_area', 32)->default('front');
            $table->string('status', 20)->default('DRAFT')->index();
            $table->string('source_hash', 64)->nullable();
            $table->string('print_file_hash', 64)->nullable();
            $table->string('approved_hash', 64)->nullable();    // hash approuvé : doit égaler print_file_hash
            $table->string('public_url', 1024)->nullable();
            $table->string('rel_path', 512)->nullable();
            $table->string('mockup_path', 512)->nullable();
            $table->unsignedInteger('width_mm')->nullable();
            $table->unsignedInteger('height_mm')->nullable();
            $table->string('zone_version', 64)->nullable();
            $table->unsignedInteger('dpi')->nullable();
            $table->string('profile', 64)->nullable();
            $table->string('engine_version', 32)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'variant_uid', 'print_area'], 'shop_print_files_target_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_print_files');
    }
};
