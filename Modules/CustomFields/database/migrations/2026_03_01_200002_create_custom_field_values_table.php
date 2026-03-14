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
        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_field_definition_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('fieldable_type');
            $table->unsignedBigInteger('fieldable_id');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->index(['fieldable_type', 'fieldable_id']);
            $table->unique([
                'custom_field_definition_id',
                'fieldable_type',
                'fieldable_id',
            ], 'custom_field_unique_assignment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
    }
};
