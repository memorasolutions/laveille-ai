<?php

declare(strict_types=1);

namespace Modules\Dictionary\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GlossaryBroaderNarrowerSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = __DIR__.'/data/glossary_broader_narrower.json';

        if (! file_exists($filePath)) {
            echo "[Seeder] JSON file not found: {$filePath}\n";

            return;
        }

        $data = json_decode((string) file_get_contents($filePath), true, 512, JSON_THROW_ON_ERROR);

        $parentCount = 0;
        $childCount = 0;

        foreach ($data as $entry) {
            $parent = $entry['parent'];
            $narrowers = $entry['narrower'];

            $updatedParent = DB::table('dictionary_terms')
                ->whereRaw("JSON_EXTRACT(slug, '$.fr_CA') = ? OR JSON_EXTRACT(slug, '$.fr') = ?", [$parent, $parent])
                ->update(['narrower_slugs' => json_encode($narrowers, JSON_UNESCAPED_UNICODE)]);

            if ($updatedParent > 0) {
                $parentCount++;
            }

            foreach ($narrowers as $narrowerSlug) {
                $existingBroader = DB::table('dictionary_terms')
                    ->whereRaw("JSON_EXTRACT(slug, '$.fr_CA') = ? OR JSON_EXTRACT(slug, '$.fr') = ?", [$narrowerSlug, $narrowerSlug])
                    ->value('broader_slugs');

                $broaderArray = $existingBroader ? json_decode($existingBroader, true, 512, JSON_THROW_ON_ERROR) : [];
                if (! is_array($broaderArray)) {
                    $broaderArray = [];
                }

                if (! in_array($parent, $broaderArray, true)) {
                    $broaderArray[] = $parent;
                }

                $updatedChild = DB::table('dictionary_terms')
                    ->whereRaw("JSON_EXTRACT(slug, '$.fr_CA') = ? OR JSON_EXTRACT(slug, '$.fr') = ?", [$narrowerSlug, $narrowerSlug])
                    ->update(['broader_slugs' => json_encode($broaderArray, JSON_UNESCAPED_UNICODE)]);

                if ($updatedChild > 0) {
                    $childCount++;
                }
            }
        }

        echo "[Seeder] {$parentCount} parents, {$childCount} children updated\n";
    }
}
