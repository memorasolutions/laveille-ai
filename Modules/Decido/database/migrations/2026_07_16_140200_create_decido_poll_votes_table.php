<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decido_poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('decido_polls')->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('decido_poll_options')->cascadeOnDelete();
            $table->uuid('voter_token');
            $table->string('voter_pseudonym', 100);
            $table->string('value', 20);
            $table->timestamps();

            $table->unique(['option_id', 'voter_token']);
            $table->index(['poll_id', 'voter_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decido_poll_votes');
    }
};
