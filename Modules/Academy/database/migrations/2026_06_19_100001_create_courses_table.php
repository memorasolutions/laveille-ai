<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('courses')) {
            return;
        }

        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('language')->default('fr-CA');
            $table->string('level')->default('intro'); // intro|inter|avance
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedBigInteger('image_media_id')->nullable();
            $table->string('visibility')->default('public'); // public|unlisted|private
            $table->string('access_type')->default('free'); // free|paid_one_time|paid_subscription
            $table->unsignedInteger('price_cents')->nullable();
            $table->string('currency', 3)->default('CAD');
            $table->string('stripe_price_id')->nullable();
            $table->string('status')->default('draft'); // draft|published|archived
            $table->timestamp('published_at')->nullable();
            $table->json('seo_jsonld')->nullable();
            $table->json('faq_dictionary_ids')->nullable();
            $table->unsignedBigInteger('tools_collection_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
