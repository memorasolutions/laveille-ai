<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Phase 3 Education : 5 champs additifs (total 10 champs Education avec S33).
     */
    public function up(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->boolean('is_academic_discount')->nullable()->after('education_last_checked_at');
            $table->json('education_level')->nullable()->after('is_academic_discount');
            $table->string('privacy_compliance', 100)->nullable()->after('education_level');
            $table->unsignedTinyInteger('learning_curve')->nullable()->after('privacy_compliance');
            $table->boolean('has_api_access')->default(false)->after('learning_curve');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->dropColumn([
                'is_academic_discount',
                'education_level',
                'privacy_compliance',
                'learning_curve',
                'has_api_access',
            ]);
        });
    }
};
