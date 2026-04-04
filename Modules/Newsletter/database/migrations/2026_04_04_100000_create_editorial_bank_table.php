<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editorial_bank', function (Blueprint $table) {
            $table->id();
            $table->string('theme', 50)->index();
            $table->text('content');
            $table->string('author')->default('Stéphane');
            $table->integer('used_count')->default(0);
            $table->dateTime('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_bank');
    }
};
