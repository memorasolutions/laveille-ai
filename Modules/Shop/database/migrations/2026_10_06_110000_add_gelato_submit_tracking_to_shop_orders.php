<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Additive et réversible : colonnes nullables de suivi de la soumission Gelato (état UNKNOWN, orphelins, commandes payées non honorables).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->timestamp('gelato_submit_started_at')->nullable();
            $table->string('gelato_submit_state', 20)->nullable();   // submitting | unknown | rejected | submitted
            $table->text('gelato_issue')->nullable();                // enregistrement persistant (n'écrase pas notes)
        });
    }

    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropColumn(['gelato_submit_started_at', 'gelato_submit_state', 'gelato_issue']);
        });
    }
};
