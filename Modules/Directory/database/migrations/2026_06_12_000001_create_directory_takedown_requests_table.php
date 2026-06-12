<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('directory_takedown_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('directory_tool_id')->nullable();
            $table->foreign('directory_tool_id')
                ->references('id')
                ->on('directory_tools')
                ->nullOnDelete();
            $table->string('target_url');
            $table->string('requester_name');
            $table->string('requester_email');
            $table->string('requester_organization')->nullable();
            $table->string('requester_role'); // titulaire/mandataire/avocat/autre
            $table->string('right_type'); // droit_auteur/marque/vie_privee/autre
            $table->text('right_details');
            $table->text('description');
            $table->boolean('declaration_accepted')->default(false);
            $table->string('status')->default('pending');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('directory_takedown_requests');
    }
};
