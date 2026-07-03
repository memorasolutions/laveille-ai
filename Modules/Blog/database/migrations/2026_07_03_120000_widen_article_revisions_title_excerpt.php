<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * `title` et `excerpt` d'article_revisions stockent le JSON multilingue complet
 * (Spatie Translatable, toutes les locales), pas une seule chaine courte -
 * une limite fixe (string 255 / string 500) plante des que le JSON depasse.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_revisions', function (Blueprint $table) {
            $table->text('title')->change();
            $table->text('excerpt')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('article_revisions', function (Blueprint $table) {
            $table->string('title')->change();
            $table->string('excerpt', 500)->nullable()->change();
        });
    }
};
