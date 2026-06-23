<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V5a-3 : CONTRAINTE UNIQUE (user_id, course_id) sur certificates_issued.
 *
 * Renforce la protection anti-doublon déjà offerte par firstOrCreate+DB::transaction
 * dans CertificateService : même sous forte concurrence, la base de données refuse
 * la création d'un second certificat pour le même (user, cours). Laravel catchera la
 * UniqueConstraintViolationException levée par firstOrCreate et renverra la ligne
 * existante.
 *
 * Migration ADDITIVE et idempotente (guard hasTable + try-catch sur addUnique).
 * down() retire l'index UNIQUEMENT (aucune perte de données).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('certificates_issued')) {
            return;
        }

        try {
            Schema::table('certificates_issued', function (Blueprint $table): void {
                $table->unique(['user_id', 'course_id'], 'certificates_issued_user_course_unique');
            });
        } catch (\Throwable) {
            // Index déjà présent (environnement où la migration aurait tourné deux fois) : no-op.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('certificates_issued')) {
            return;
        }

        try {
            Schema::table('certificates_issued', function (Blueprint $table): void {
                $table->dropUnique('certificates_issued_user_course_unique');
            });
        } catch (\Throwable) {
            // Index absent : no-op.
        }
    }
};
