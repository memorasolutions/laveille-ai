<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('source_type')->default('manual'); // manual, faq, page, article, service
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('content');
            $table->json('metadata')->nullable(); // catégorie, tags, priorité, url
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->timestamps();

            $table->index('source_type');
            $table->index('is_active');
            $table->index('tenant_id');
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('ai_knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('ai_knowledge_documents')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->text('content');
            $table->longText('embedding')->nullable(); // JSON array de floats
            $table->unsignedInteger('token_count')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index('document_id');
        });

        // FULLTEXT index pour fallback sans embeddings (MySQL/MariaDB seulement)
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE ai_knowledge_chunks ADD FULLTEXT INDEX ft_chunk_content (content)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_chunks');
        Schema::dropIfExists('ai_knowledge_documents');
    }
};
