<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * DISCUSSION SOCIALE PAR VIDÉO (dette D-video-discussion, LMS 2026) : ancre
 * facultative d'une RÉPONSE de forum à un instant précis de la vidéo (secondes
 * depuis le début), symétrique à academy_forum_topics.video_timestamp_seconds.
 * Permet de trier le fil d'un sujet dans l'ordre du contenu vidéo plutôt que
 * dans l'ordre de publication. NULL = comportement identique à avant. Migration
 * ADDITIVE et idempotente (guard hasColumn).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academy_forum_posts') || Schema::hasColumn('academy_forum_posts', 'video_timestamp_seconds')) {
            return;
        }

        Schema::table('academy_forum_posts', function (Blueprint $table): void {
            $table->unsignedInteger('video_timestamp_seconds')->nullable()->default(null)->after('body');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('academy_forum_posts') && Schema::hasColumn('academy_forum_posts', 'video_timestamp_seconds')) {
            Schema::table('academy_forum_posts', function (Blueprint $table): void {
                $table->dropColumn('video_timestamp_seconds');
            });
        }
    }
};
