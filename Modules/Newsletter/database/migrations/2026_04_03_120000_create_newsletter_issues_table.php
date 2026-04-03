<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('week_number');
            $table->unsignedSmallInteger('year');
            $table->string('subject', 255);
            $table->json('content')->nullable();
            $table->unsignedInteger('subscriber_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['year', 'week_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_issues');
    }
};
