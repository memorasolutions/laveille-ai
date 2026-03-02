<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
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
        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            $table->morphs('revisionable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('data');
            $table->integer('revision_number')->default(1);
            $table->string('summary')->nullable();
            $table->timestamps();

            $table->index(['revisionable_type', 'revisionable_id', 'revision_number'], 'content_revisions_composite_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_revisions');
    }
};
