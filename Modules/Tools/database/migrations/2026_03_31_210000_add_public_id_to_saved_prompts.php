<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_prompts', function (Blueprint $table) {
            $table->string('public_id', 12)->unique()->after('id')->nullable();
        });

        // Remplir les public_id existants
        foreach (\Illuminate\Support\Facades\DB::table('saved_prompts')->get() as $row) {
            \Illuminate\Support\Facades\DB::table('saved_prompts')
                ->where('id', $row->id)
                ->update(['public_id' => Str::random(12)]);
        }
    }

    public function down(): void
    {
        Schema::table('saved_prompts', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
    }
};
