<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->unsignedBigInteger('submitted_by')->nullable()->after('user_id');
            $table->string('submission_status', 20)->nullable()->default(null)->after('submitted_by');

            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('user_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('bookmarkable_type');
            $table->unsignedBigInteger('bookmarkable_id');
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'bookmarkable_type', 'bookmarkable_id']);
            $table->index(['bookmarkable_type', 'bookmarkable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropColumn(['submitted_by', 'submission_status']);
        });

        Schema::dropIfExists('user_bookmarks');
    }
};
