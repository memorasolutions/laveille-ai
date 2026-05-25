<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dictionary_terms')) {
            return;
        }
        if (Schema::hasColumn('dictionary_terms', 'reference_url')) {
            return;
        }
        Schema::table('dictionary_terms', function (Blueprint $table): void {
            $table->string('reference_url')->nullable()->after('hero_image');
            $table->string('reference_label')->nullable()->after('reference_url');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('dictionary_terms') || ! Schema::hasColumn('dictionary_terms', 'reference_url')) {
            return;
        }
        Schema::table('dictionary_terms', function (Blueprint $table): void {
            $table->dropColumn(['reference_url', 'reference_label']);
        });
    }
};
