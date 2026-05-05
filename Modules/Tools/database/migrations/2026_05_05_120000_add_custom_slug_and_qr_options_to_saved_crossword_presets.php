<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_crossword_presets', function (Blueprint $table) {
            if (! Schema::hasColumn('saved_crossword_presets', 'custom_slug')) {
                $table->string('custom_slug', 50)->nullable()->unique()->after('public_id');
            }
            if (! Schema::hasColumn('saved_crossword_presets', 'qr_options')) {
                $table->json('qr_options')->nullable()->after('custom_slug');
            }
        });
    }

    public function down(): void
    {
        Schema::table('saved_crossword_presets', function (Blueprint $table) {
            if (Schema::hasColumn('saved_crossword_presets', 'qr_options')) {
                $table->dropColumn('qr_options');
            }
            if (Schema::hasColumn('saved_crossword_presets', 'custom_slug')) {
                $table->dropUnique(['custom_slug']);
                $table->dropColumn('custom_slug');
            }
        });
    }
};
