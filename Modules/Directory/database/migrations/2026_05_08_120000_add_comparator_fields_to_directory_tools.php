<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_tools', function (Blueprint $table): void {
            $table->string('underlying_model', 120)->nullable()->after('has_api_access');
            $table->boolean('is_multimodal')->nullable()->after('underlying_model');
            $table->json('output_types')->nullable()->after('is_multimodal');
            $table->enum('opt_out_training', ['yes', 'no', 'unknown'])->default('unknown')->after('output_types');
            $table->string('unique_value', 280)->nullable()->after('opt_out_training');
        });
    }

    public function down(): void
    {
        Schema::table('directory_tools', function (Blueprint $table): void {
            $table->dropColumn([
                'underlying_model',
                'is_multimodal',
                'output_types',
                'opt_out_training',
                'unique_value',
            ]);
        });
    }
};
