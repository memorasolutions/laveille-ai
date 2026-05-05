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
            if (! Schema::hasColumn('saved_crossword_presets', 'fingerprint')) {
                $table->string('fingerprint', 64)->nullable()->index()->after('config_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('saved_crossword_presets', function (Blueprint $table) {
            if (Schema::hasColumn('saved_crossword_presets', 'fingerprint')) {
                $table->dropIndex(['fingerprint']);
                $table->dropColumn('fingerprint');
            }
        });
    }
};
