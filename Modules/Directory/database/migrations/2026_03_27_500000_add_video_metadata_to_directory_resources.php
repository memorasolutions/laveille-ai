<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_resources', function (Blueprint $table): void {
            $table->unsignedInteger('duration_seconds')->nullable()->after('video_summary');
            $table->string('channel_name', 255)->nullable()->after('duration_seconds');
            $table->string('channel_url', 500)->nullable()->after('channel_name');
        });
    }

    public function down(): void
    {
        Schema::table('directory_resources', function (Blueprint $table): void {
            $table->dropColumn(['duration_seconds', 'channel_name', 'channel_url']);
        });
    }
};
