<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('directory_tools', 'prices_converted_cad_at')) {
            Schema::table('directory_tools', function (Blueprint $table): void {
                $table->timestamp('prices_converted_cad_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('directory_tools', 'prices_converted_cad_at')) {
            Schema::table('directory_tools', function (Blueprint $table): void {
                $table->dropColumn('prices_converted_cad_at');
            });
        }
    }
};
