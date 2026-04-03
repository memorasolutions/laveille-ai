<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('newsletter_issues')) {
            return;
        }

        Schema::table('newsletter_issues', function (Blueprint $table) {
            if (! Schema::hasColumn('newsletter_issues', 'status')) {
                $table->string('status', 20)->default('draft')->after('subject');
            }
            if (! Schema::hasColumn('newsletter_issues', 'editorial_edited')) {
                $table->text('editorial_edited')->nullable()->after('content');
            }
            if (! Schema::hasColumn('newsletter_issues', 'edited_at')) {
                $table->timestamp('edited_at')->nullable()->after('sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_issues', function (Blueprint $table) {
            $table->dropColumn(['status', 'editorial_edited', 'edited_at']);
        });
    }
};
