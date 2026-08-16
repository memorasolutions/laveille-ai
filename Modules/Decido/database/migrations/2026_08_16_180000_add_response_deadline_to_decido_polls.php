<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LOT 1 (docs/specs/2026-08-16-decido-reste-a-faire.md, point 2) : échéance de réponse
 * FACULTATIVE. Stockée en UTC brut (même convention que decido_poll_options.starts_at/ends_at -
 * voir SlotGenerationService/PollExportService::exportIcs, qui reparsent explicitement la valeur
 * comme UTC avant conversion vers le fuseau du sondage). Jamais bloquante par défaut : aucune
 * colonne ni logique de verrouillage n'est ajoutée ici - la date passée est seulement affichée
 * comme un avertissement (PublicPollController::show()/vote.blade.php), le vote reste accepté.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decido_polls', function (Blueprint $table) {
            $table->timestamp('response_deadline_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('decido_polls', function (Blueprint $table) {
            $table->dropColumn('response_deadline_at');
        });
    }
};
