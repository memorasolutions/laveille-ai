<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_pricing_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tool_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('reported_pricing', 32)->comment('Tarification proposee par utilisateur');
            $table->string('current_pricing_snapshot', 32)->nullable()->comment('Tarification actuelle au moment du signalement');
            $table->string('evidence_url', 500)->nullable();
            $table->text('user_notes')->nullable();
            $table->string('status', 32)->default('pending')->comment('pending, approved, rejected');
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('tool_id', 'tool_pricing_reports_tool_id_fk')
                ->references('id')
                ->on('directory_tools')
                ->cascadeOnDelete();

            $table->foreign('user_id', 'tool_pricing_reports_user_id_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('reviewed_by', 'tool_pricing_reports_reviewed_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['tool_id', 'status'], 'tool_pricing_reports_tool_status_idx');
            $table->index(['status', 'created_at'], 'tool_pricing_reports_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_pricing_reports');
    }
};
