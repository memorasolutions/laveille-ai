<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jeton participant CHIFFRÉ (Crypt) pour permettre la réimpression du PDF de QR.
 * token_hash reste l'index aveugle de recherche; token_cipher n'est jamais en clair.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('fa_participants', 'token_cipher')) {
            Schema::table('fa_participants', function (Blueprint $table) {
                $table->text('token_cipher')->nullable()->after('token_hash');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fa_participants', 'token_cipher')) {
            Schema::table('fa_participants', function (Blueprint $table) {
                $table->dropColumn('token_cipher');
            });
        }
    }
};
