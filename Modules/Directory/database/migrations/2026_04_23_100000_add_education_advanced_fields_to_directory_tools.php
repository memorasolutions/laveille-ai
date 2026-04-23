<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            if (! Schema::hasColumn('directory_tools', 'education_discount_type')) {
                $table->string('education_discount_type', 30)->nullable()->index()->after('education_pricing_url');
            }

            if (! Schema::hasColumn('directory_tools', 'education_target_audience')) {
                $table->json('education_target_audience')->nullable()->after('education_discount_type');
            }

            if (! Schema::hasColumn('directory_tools', 'education_verification_required')) {
                $table->boolean('education_verification_required')->default(false)->after('education_target_audience');
            }

            if (! Schema::hasColumn('directory_tools', 'education_official_url')) {
                $table->string('education_official_url', 500)->nullable()->after('education_verification_required');
            }

            if (! Schema::hasColumn('directory_tools', 'education_last_checked_at')) {
                $table->timestamp('education_last_checked_at')->nullable()->after('education_official_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->dropColumn([
                'education_discount_type',
                'education_target_audience',
                'education_verification_required',
                'education_official_url',
                'education_last_checked_at',
            ]);
        });
    }
};
