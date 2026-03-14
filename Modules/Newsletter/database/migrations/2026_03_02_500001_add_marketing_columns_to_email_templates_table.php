<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->json('json_content')->nullable()->after('variables');
            $table->string('category', 100)->nullable()->index()->after('json_content');
            $table->foreignId('tenant_id')->nullable()->after('category')->constrained('tenants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['json_content', 'category', 'tenant_id']);
        });
    }
};
