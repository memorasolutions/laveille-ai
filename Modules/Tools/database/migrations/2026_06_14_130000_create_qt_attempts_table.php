<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parties enregistrées du quiz « QT — Quotient Techno » (preuve sociale + percentile).
 * Aucune donnée personnelle : seulement le score, le mode, le numéro de défi et le nb de bonnes réponses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qt_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('qt');
            $table->string('mode', 10); // 'defi' | 'libre'
            $table->unsignedInteger('defi_number')->nullable();
            $table->unsignedTinyInteger('correct_count');
            $table->timestamp('created_at')->nullable();

            $table->index('created_at');
            $table->index(['mode', 'defi_number']);
            $table->index('qt');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('qt_attempts')) {
            Schema::dropIfExists('qt_attempts');
        }
    }
};
