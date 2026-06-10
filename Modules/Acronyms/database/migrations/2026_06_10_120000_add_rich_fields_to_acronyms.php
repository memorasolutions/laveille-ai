<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #304 — Enrichissement des acronymes à parité avec le glossaire (AEO/SEO/GEO).
 * Ajoute les champs riches (réponse courte, analogie, exemple, le saviez-vous, FAQ,
 * sources, icône, difficulté, références, maillage). Tous nullable → rétro-compatible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acronyms', function (Blueprint $table) {
            $table->json('one_sentence_answer')->nullable()->after('description');
            $table->json('analogy')->nullable()->after('one_sentence_answer');
            $table->json('example')->nullable()->after('analogy');
            $table->json('did_you_know')->nullable()->after('example');
            $table->json('faq')->nullable()->after('did_you_know');
            $table->json('sources')->nullable()->after('faq');
            $table->string('icon', 16)->nullable()->after('sources');
            $table->string('difficulty', 20)->nullable()->after('icon');
            $table->string('reference_url', 500)->nullable()->after('difficulty');
            $table->string('reference_label', 255)->nullable()->after('reference_url');
            $table->json('broader_slugs')->nullable()->after('reference_label');
            $table->json('narrower_slugs')->nullable()->after('broader_slugs');
        });
    }

    public function down(): void
    {
        Schema::table('acronyms', function (Blueprint $table) {
            $table->dropColumn([
                'one_sentence_answer', 'analogy', 'example', 'did_you_know', 'faq', 'sources',
                'icon', 'difficulty', 'reference_url', 'reference_label', 'broader_slugs', 'narrower_slugs',
            ]);
        });
    }
};
