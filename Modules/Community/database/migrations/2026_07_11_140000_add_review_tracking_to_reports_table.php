<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project la-veille-de-stef-v2
 *
 * Traçabilité du traitement des signalements (preuve de diligence : date de
 * réception = created_at, analyse = resolution_notes, action = status +
 * reviewed_at + handled_by). Requis par le design du Journal personnel
 * (engagement de traitement documenté sous 48h).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->timestamp('reviewed_at')->nullable()->after('status');
            $table->text('resolution_notes')->nullable()->after('reviewed_at');
            $table->foreignId('handled_by')->nullable()->after('resolution_notes')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handled_by');
            $table->dropColumn(['reviewed_at', 'resolution_notes']);
        });
    }
};
