<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->boolean('has_education_pricing')->default(false)->index()->after('pricing');
            $table->string('education_pricing_type', 20)->nullable()->after('has_education_pricing');
            $table->json('education_pricing_details')->nullable()->after('education_pricing_type');
            $table->string('education_pricing_url', 500)->nullable()->after('education_pricing_details');
        });
    }

    public function down(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->dropIndex(['has_education_pricing']);
            $table->dropColumn([
                'has_education_pricing',
                'education_pricing_type',
                'education_pricing_details',
                'education_pricing_url',
            ]);
        });
    }
};
