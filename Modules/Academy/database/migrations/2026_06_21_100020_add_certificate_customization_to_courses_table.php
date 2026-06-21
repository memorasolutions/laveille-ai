<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Personnalisation du CERTIFICAT au niveau du COURS (Phase E / E3).
 * Champs facultatifs : un certificat émis lit ces valeurs du cours associé et les
 * applique AU-DESSUS des défauts du gabarit. Toute valeur NULL → on retombe sur le
 * défaut actuel (rétrocompatibilité totale : aucun certificat existant n'est cassé).
 *
 * Migration ADDITIVE et idempotente (guard Schema::hasColumn par colonne).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Colonnes ajoutées (toutes nullable → défaut = comportement actuel). */
    private const COLUMNS = [
        'certificate_title',
        'certificate_message',
        'certificate_signature_name',
        'certificate_accent_color',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('courses')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table): void {
            if (! Schema::hasColumn('courses', 'certificate_title')) {
                $table->string('certificate_title')->nullable()->after('seo_jsonld');
            }
            if (! Schema::hasColumn('courses', 'certificate_message')) {
                $table->text('certificate_message')->nullable()->after('certificate_title');
            }
            if (! Schema::hasColumn('courses', 'certificate_signature_name')) {
                $table->string('certificate_signature_name')->nullable()->after('certificate_message');
            }
            if (! Schema::hasColumn('courses', 'certificate_accent_color')) {
                $table->string('certificate_accent_color', 7)->nullable()->after('certificate_signature_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('courses')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table): void {
            foreach (self::COLUMNS as $column) {
                if (Schema::hasColumn('courses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
