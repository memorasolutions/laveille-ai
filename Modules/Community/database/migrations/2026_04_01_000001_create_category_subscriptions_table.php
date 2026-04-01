<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_subscriptions')) {
            Schema::create('category_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('category_tag', 100);
                $table->string('module', 50);
                $table->timestamps();

                $table->unique(['user_id', 'category_tag', 'module']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('category_subscriptions');
    }
};
