<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_sent_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->unsignedBigInteger('item_id');
            $table->unsignedSmallInteger('week_number');
            $table->unsignedSmallInteger('year');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'item_id']);
            $table->index(['year', 'week_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_sent_items');
    }
};
