<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('decido_polls', 'short_url_id')) return;
        Schema::table('decido_polls', function (Blueprint $table) {
            $table->unsignedBigInteger('short_url_id')->nullable()->after('custom_slug');
        });
    }

    public function down(): void
    {
        Schema::table('decido_polls', function (Blueprint $table) {
            $table->dropColumn('short_url_id');
        });
    }
};
