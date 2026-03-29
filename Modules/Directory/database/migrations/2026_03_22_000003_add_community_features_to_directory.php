<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_discussions', function (Blueprint $table) {
            $table->string('title', 255)->nullable();
        });

        Schema::table('directory_reviews', function (Blueprint $table) {
            $table->unsignedInteger('upvotes')->default(0);
        });

        Schema::table('directory_resources', function (Blueprint $table) {
            $table->unsignedInteger('upvotes')->default(0);
        });

        Schema::create('directory_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('reportable_id');
            $table->string('reportable_type');
            $table->string('reason', 50);
            $table->text('comment')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
            $table->index(['reportable_type', 'reportable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('directory_reports');
        Schema::table('directory_resources', function (Blueprint $table) {
            $table->dropColumn('upvotes');
        });
        Schema::table('directory_reviews', function (Blueprint $table) {
            $table->dropColumn('upvotes');
        });
        Schema::table('directory_discussions', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
