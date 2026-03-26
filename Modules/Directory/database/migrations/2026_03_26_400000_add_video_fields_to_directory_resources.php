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
            $table->string('video_id', 20)->nullable()->after('thumbnail');
            $table->text('video_summary')->nullable()->after('video_id');
        });
    }

    public function down(): void
    {
        Schema::table('directory_resources', function (Blueprint $table): void {
            $table->dropColumn(['video_id', 'video_summary']);
        });
    }
};
