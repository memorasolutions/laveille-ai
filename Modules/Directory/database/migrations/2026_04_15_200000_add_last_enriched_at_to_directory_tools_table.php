<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->timestamp('last_enriched_at')->nullable()->after('updated_at');
            $table->unsignedTinyInteger('enrichment_version')->default(1)->after('last_enriched_at');
            $table->index('last_enriched_at');
        });
    }

    public function down(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->dropIndex(['last_enriched_at']);
            $table->dropColumn(['last_enriched_at', 'enrichment_version']);
        });
    }
};
