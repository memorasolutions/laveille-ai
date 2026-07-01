<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * DISCUSSION SOCIALE PAR VIDÉO (dette D-video-discussion, LMS 2026) : ancre
 * facultative d'un sujet de forum à un instant précis de la vidéo (secondes
 * depuis le début). Réutilise le forum EXISTANT (ForumTopic/ForumPost) au lieu
 * d'un nouveau système : un item de leçon « video » peut porter son propre
 * forum (sujets scopés à son lesson_item_id, comme un item « forum » classique)
 * quand ACADEMY_VIDEO_DISCUSSION_ENABLED=true. NULL = comportement identique à
 * avant (aucun ancrage, forum texte simple). Migration ADDITIVE et idempotente
 * (guard hasColumn).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academy_forum_topics') || Schema::hasColumn('academy_forum_topics', 'video_timestamp_seconds')) {
            return;
        }

        Schema::table('academy_forum_topics', function (Blueprint $table): void {
            $table->unsignedInteger('video_timestamp_seconds')->nullable()->default(null)->after('body');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('academy_forum_topics') && Schema::hasColumn('academy_forum_topics', 'video_timestamp_seconds')) {
            Schema::table('academy_forum_topics', function (Blueprint $table): void {
                $table->dropColumn('video_timestamp_seconds');
            });
        }
    }
};
