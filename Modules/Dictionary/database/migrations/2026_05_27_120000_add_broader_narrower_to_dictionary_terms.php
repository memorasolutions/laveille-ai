<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dictionary_terms')) {
            return;
        }

        Schema::table('dictionary_terms', function (Blueprint $table): void {
            if (! Schema::hasColumn('dictionary_terms', 'broader_slugs')) {
                $table->json('broader_slugs')->nullable()->after('sources');
            }
            if (! Schema::hasColumn('dictionary_terms', 'narrower_slugs')) {
                $table->json('narrower_slugs')->nullable()->after('broader_slugs');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('dictionary_terms')) {
            return;
        }

        Schema::table('dictionary_terms', function (Blueprint $table): void {
            foreach (['broader_slugs', 'narrower_slugs'] as $col) {
                if (Schema::hasColumn('dictionary_terms', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
