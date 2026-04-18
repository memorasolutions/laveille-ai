<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favicon_cache', function (Blueprint $t): void {
            $t->id();
            $t->string('domain', 190)->unique();
            $t->string('resolved_url', 500)->nullable();
            $t->unsignedTinyInteger('failed_count')->default(0);
            $t->timestamp('checked_at')->nullable()->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favicon_cache');
    }
};
