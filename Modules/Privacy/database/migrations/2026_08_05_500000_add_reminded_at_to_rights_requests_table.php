<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rights_requests', function (Blueprint $table) {
            // Anti-spam idempotent pour privacy:remind-overdue-requests : une fois le
            // rappel envoye au DPO, on ne renotifie plus jamais la meme demande.
            $table->timestamp('reminded_at')->nullable()->after('responded_at');
        });
    }

    public function down(): void
    {
        Schema::table('rights_requests', function (Blueprint $table) {
            $table->dropColumn('reminded_at');
        });
    }
};
