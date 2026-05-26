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
            if (! Schema::hasColumn('dictionary_terms', 'one_sentence_answer')) {
                $table->json('one_sentence_answer')->nullable()->after('did_you_know');
            }
            if (! Schema::hasColumn('dictionary_terms', 'faq')) {
                $table->json('faq')->nullable()->after('one_sentence_answer');
            }
            if (! Schema::hasColumn('dictionary_terms', 'sources')) {
                $table->json('sources')->nullable()->after('faq');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('dictionary_terms')) {
            return;
        }

        Schema::table('dictionary_terms', function (Blueprint $table): void {
            foreach (['one_sentence_answer', 'faq', 'sources'] as $col) {
                if (Schema::hasColumn('dictionary_terms', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
