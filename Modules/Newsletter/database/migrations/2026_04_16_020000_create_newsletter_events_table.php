<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('subscriber_id')
                ->nullable()
                ->constrained('newsletter_subscribers')
                ->nullOnDelete();
            $table->string('email', 255)->index();
            $table->string('event', 20)->index();
            $table->string('message_id', 255)->nullable()->index();
            $table->text('link')->nullable();
            $table->string('ip', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['email', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_events');
    }
};
