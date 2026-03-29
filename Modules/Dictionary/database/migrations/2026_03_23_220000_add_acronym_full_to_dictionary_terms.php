<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dictionary_terms', function (Blueprint $table) {
            $table->string('acronym_full')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('dictionary_terms', function (Blueprint $table) {
            $table->dropColumn('acronym_full');
        });
    }
};
