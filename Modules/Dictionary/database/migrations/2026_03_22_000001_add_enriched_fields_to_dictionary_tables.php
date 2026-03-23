<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dictionary_terms', function (Blueprint $table) {
            $table->json('analogy')->nullable();
            $table->json('example')->nullable();
            $table->json('did_you_know')->nullable();
            $table->string('difficulty')->default('beginner');
            $table->string('icon')->nullable();
        });

        Schema::table('dictionary_categories', function (Blueprint $table) {
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('dictionary_terms', function (Blueprint $table) {
            $table->dropColumn(['analogy', 'example', 'did_you_know', 'difficulty', 'icon']);
        });

        Schema::table('dictionary_categories', function (Blueprint $table) {
            $table->dropColumn(['icon', 'color']);
        });
    }
};
