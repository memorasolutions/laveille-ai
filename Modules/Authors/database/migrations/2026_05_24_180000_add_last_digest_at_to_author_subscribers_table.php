<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('author_subscribers')) {
            return;
        }
        if (Schema::hasColumn('author_subscribers', 'last_digest_at')) {
            return;
        }
        Schema::table('author_subscribers', function (Blueprint $table): void {
            $table->timestamp('last_digest_at')->nullable()->after('confirmed_at');
            $table->index('last_digest_at', 'as_sub_lda_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('author_subscribers')) {
            return;
        }
        if (! Schema::hasColumn('author_subscribers', 'last_digest_at')) {
            return;
        }
        Schema::table('author_subscribers', function (Blueprint $table): void {
            $table->dropIndex('as_sub_lda_idx');
            $table->dropColumn('last_digest_at');
        });
    }
};
