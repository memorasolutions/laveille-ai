<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('author_affiliate_links')) {
            return;
        }

        Schema::create('author_affiliate_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_profile_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 100)->unique();
            $table->string('destination_url', 500);
            $table->string('label', 200)->nullable();
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['author_profile_id', 'slug'], 'aal_profile_slug_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_affiliate_links');
    }
};
