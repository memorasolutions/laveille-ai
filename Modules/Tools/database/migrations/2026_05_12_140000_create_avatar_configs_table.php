<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avatar_configs', function (Blueprint $table) {
            $table->id();
            $table->string('user_email')->nullable()->index();
            $table->string('slug', 32)->unique()->index();
            $table->json('config');
            $table->boolean('is_public')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avatar_configs');
    }
};
