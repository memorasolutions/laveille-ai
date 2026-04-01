<?php

namespace Modules\Newsletter\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MailsterSubscribersSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = __DIR__ . '/data/mailster_subscribers.json';

        if (!file_exists($filePath)) {
            $this->command->error("Fichier JSON introuvable: {$filePath}");
            return;
        }

        $subscribers = json_decode(file_get_contents($filePath), true);

        if (!is_array($subscribers)) {
            $this->command->error("Erreur JSON: " . json_last_error_msg());
            return;
        }

        $imported = 0;

        foreach ($subscribers as $sub) {
            DB::table('newsletter_subscribers')->updateOrInsert(
                ['email' => strtolower(trim($sub['email']))],
                [
                    'name' => $sub['name'] ?? null,
                    'token' => bin2hex(random_bytes(32)),
                    'confirmed_at' => ($sub['status'] == 1) ? Carbon::createFromTimestamp($sub['signup']) : null,
                    'unsubscribed_at' => null,
                    'created_at' => Carbon::createFromTimestamp($sub['signup']),
                    'updated_at' => now(),
                ]
            );
            $imported++;
        }

        $this->command->info("47 abonnes Mailster WordPress importes ({$imported} traites).");
    }
}
