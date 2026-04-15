<?php

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Directory\Models\Tag;

class EcosystemTagsSeeder extends Seeder
{
    public function run(): void
    {
        $ecosystems = [
            'Anthropic', 'OpenAI', 'Google', 'Meta', 'Microsoft',
            'Stability AI', 'Mistral', 'Adobe', 'Atlassian',
            'Runway', 'ElevenLabs', 'Jasper', 'Notion',
        ];

        foreach ($ecosystems as $name) {
            $slug = Str::kebab($name);

            Tag::updateOrCreate(
                ['slug->fr_CA' => $slug],
                [
                    'name' => ['fr_CA' => $name],
                    'slug' => ['fr_CA' => $slug],
                ]
            );
        }
    }
}
