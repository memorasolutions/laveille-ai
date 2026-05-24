<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('slug', 80)->unique();
            $table->string('cover_image')->nullable();
            $table->string('profile_image')->nullable();
            $table->text('bio')->nullable();
            $table->text('manifesto')->nullable();
            $table->enum('accent_color', [
                'teal', 'indigo', 'rose', 'amber', 'emerald', 'violet', 'sky', 'fuchsia'
            ])->default('teal');
            $table->enum('font_family', ['jakarta', 'inter', 'merriweather'])->default('jakarta');
            $table->enum('tier', ['free', 'education', 'premium', 'premium_manual'])->default('free');
            $table->timestamp('tier_expires_at')->nullable();
            $table->timestamp('education_approved_at')->nullable();
            $table->foreignId('education_approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('social_links')->nullable();
            $table->json('qualifications')->nullable();
            $table->timestamp('last_published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tier');
            $table->index('last_published_at');
            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_profiles');
    }
};
