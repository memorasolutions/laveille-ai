<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('directory_tools', 'screenshot_locked')) {
            Schema::table('directory_tools', function (Blueprint $table): void {
                $table->boolean('screenshot_locked')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('directory_tools', 'screenshot_locked')) {
            Schema::table('directory_tools', function (Blueprint $table): void {
                $table->dropColumn('screenshot_locked');
            });
        }
    }
};
