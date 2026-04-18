<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_tools', function (Blueprint $table): void {
            $table->json('aliases')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('directory_tools', function (Blueprint $table): void {
            $table->dropColumn('aliases');
        });
    }
};
