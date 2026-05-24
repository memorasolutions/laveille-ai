<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_profile_id')->constrained()->cascadeOnDelete();
            $table->string('email', 255);
            $table->string('confirmation_token', 64)->nullable()->index('as_sub_token_idx');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->enum('source', ['inline', 'footer', 'modal'])->default('inline');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('locale', 8)->default('fr');

            $table->timestamps();

            $table->unique(['author_profile_id', 'email'], 'as_sub_profile_email_uq');
            $table->index(['author_profile_id', 'confirmed_at'], 'as_sub_profile_confirmed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_subscribers');
    }
};
