<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jeton d'affichage CHIFFRÉ (Crypt) pour que l'animateur retrouve le lien de projection
 * depuis sa page de gestion. display_token_hash reste l'index aveugle de recherche;
 * display_token_cipher n'est jamais en clair ni exposé publiquement.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('fa_boards', 'display_token_cipher')) {
            Schema::table('fa_boards', function (Blueprint $table) {
                $table->text('display_token_cipher')->nullable()->after('display_token_hash');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fa_boards', 'display_token_cipher')) {
            Schema::table('fa_boards', function (Blueprint $table) {
                $table->dropColumn('display_token_cipher');
            });
        }
    }
};
