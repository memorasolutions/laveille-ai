<?php

declare(strict_types=1);

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Models\Setting;

class SettingsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['group' => 'general', 'key' => 'site_name', 'value' => 'Laravel Core', 'type' => 'string', 'description' => 'Nom du site'],
            ['group' => 'general', 'key' => 'site_description', 'value' => 'Application Laravel Core', 'type' => 'string', 'description' => 'Description du site'],
            ['group' => 'general', 'key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'description' => 'Mode maintenance'],
            ['group' => 'mail', 'key' => 'mail_from_name', 'value' => 'Laravel Core', 'type' => 'string', 'description' => 'Nom expéditeur'],
            ['group' => 'mail', 'key' => 'mail_from_address', 'value' => 'noreply@example.com', 'type' => 'string', 'description' => 'Adresse expéditeur'],
            ['group' => 'seo', 'key' => 'meta_title', 'value' => 'Laravel Core', 'type' => 'string', 'description' => 'Titre meta par défaut', 'is_public' => true],
            ['group' => 'seo', 'key' => 'meta_description', 'value' => '', 'type' => 'string', 'description' => 'Description meta par défaut', 'is_public' => true],
        ];

        foreach ($defaults as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
