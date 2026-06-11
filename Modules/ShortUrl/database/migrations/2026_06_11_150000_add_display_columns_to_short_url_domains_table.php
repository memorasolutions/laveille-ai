<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('short_url_domains', function (Blueprint $table) {
            if (! Schema::hasColumn('short_url_domains', 'display_label')) {
                $table->string('display_label')->nullable()->after('domain');
            }

            if (! Schema::hasColumn('short_url_domains', 'hidden_in_selector')) {
                $table->boolean('hidden_in_selector')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('short_url_domains', function (Blueprint $table) {
            $table->dropColumn(['display_label', 'hidden_in_selector']);
        });
    }
};
