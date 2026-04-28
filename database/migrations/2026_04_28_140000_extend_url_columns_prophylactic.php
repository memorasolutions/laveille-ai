<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('directory_resources')) {
            Schema::table('directory_resources', function (Blueprint $table) {
                $table->string('url', 2048)->nullable()->change();
            });
        }

        if (Schema::hasTable('ai_knowledge_urls')) {
            Schema::table('ai_knowledge_urls', function (Blueprint $table) {
                $table->string('url', 2048)->nullable(false)->change();
            });
        }

        if (Schema::hasTable('webhook_endpoints')) {
            Schema::table('webhook_endpoints', function (Blueprint $table) {
                $table->string('url', 2048)->nullable(false)->change();
            });
        }

        if (Schema::hasTable('booking_webhooks')) {
            Schema::table('booking_webhooks', function (Blueprint $table) {
                $table->string('url', 2048)->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('directory_resources')) {
            Schema::table('directory_resources', function (Blueprint $table) {
                $table->string('url', 255)->nullable()->change();
            });
        }

        if (Schema::hasTable('ai_knowledge_urls')) {
            Schema::table('ai_knowledge_urls', function (Blueprint $table) {
                $table->string('url', 255)->nullable(false)->change();
            });
        }

        if (Schema::hasTable('webhook_endpoints')) {
            Schema::table('webhook_endpoints', function (Blueprint $table) {
                $table->string('url', 255)->nullable(false)->change();
            });
        }

        if (Schema::hasTable('booking_webhooks')) {
            Schema::table('booking_webhooks', function (Blueprint $table) {
                $table->string('url', 255)->nullable(false)->change();
            });
        }
    }
};
