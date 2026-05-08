<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Table d'audits pricing (multi-source consensus + screenshot evidence + freshness SLA).
 * Pattern audit-trail réutilisable pour autres champs (descriptions, lifecycle, etc.).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_pricing_audits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('directory_tool_id');
            $table->timestamp('audited_at')->index();
            $table->string('real_pricing', 32)->nullable(); // free/freemium/paid/free_trial/enterprise/open_source/education/unknown
            $table->boolean('has_education_discount')->nullable();
            $table->string('education_url', 500)->nullable();
            $table->unsignedTinyInteger('confidence')->default(0); // 0-100
            $table->unsignedTinyInteger('weighted_score')->default(0); // 0-100, agrégat sources
            $table->json('sources')->nullable(); // ['ppsearch'=>{...}, 'browser'=>{...}, 'llm'=>{...}, 'screenshot'=>{...}]
            $table->json('evidence')->nullable(); // {url, quote, html_hash}
            $table->string('screenshot_path', 500)->nullable();
            $table->enum('review_status', ['pending', 'accepted', 'rejected', 'auto_applied'])->default('pending')->index();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('directory_tool_id')
                ->references('id')->on('directory_tools')
                ->cascadeOnDelete();

            $table->index(['directory_tool_id', 'audited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_pricing_audits');
    }
};
