<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Directory\Services\ToolDiscoveryService;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('directory_tools')
            ->where('url', 'like', '%?%')
            ->select('id', 'url')
            ->orderBy('id')
            ->chunk(200, function ($tools) {
                foreach ($tools as $tool) {
                    $cleaned = ToolDiscoveryService::cleanUrl($tool->url);
                    if ($cleaned !== $tool->url) {
                        DB::table('directory_tools')
                            ->where('id', $tool->id)
                            ->update(['url' => $cleaned]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Impossible de restaurer les params supprimés.
    }
};
