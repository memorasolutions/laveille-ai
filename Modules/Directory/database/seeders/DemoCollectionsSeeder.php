<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolCollection;

class DemoCollectionsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'chatgptpro@gomemora.com')->first()
            ?? User::first();

        if (! $admin) {
            $this->command->error('Aucun utilisateur trouvé. Seeder annulé.');

            return;
        }

        // Collection 1 : Éducation
        $c1 = ToolCollection::updateOrCreate(
            ['slug' => 'outils-ia-pour-enseignants'],
            [
                'name' => 'Outils IA pour enseignants',
                'description' => "Sélection d'outils IA avec tarifs éducation (gratuit ou réduit) pour enseignants et étudiants.",
                'is_public' => true,
                'user_id' => $admin->id,
            ]
        );

        $eduIds = Tool::where('has_education_pricing', true)->orderBy('id')->pluck('id');
        $sync1 = [];
        foreach ($eduIds as $pos => $id) {
            $sync1[$id] = ['position' => $pos + 1, 'added_at' => now()];
        }
        $c1->tools()->sync($sync1);
        $this->command->info("Collection \"{$c1->name}\" : {$eduIds->count()} outil(s).");

        // Collection 2 : Assistants
        $c2 = ToolCollection::updateOrCreate(
            ['slug' => 'top-assistants-conversationnels'],
            [
                'name' => 'Top assistants conversationnels',
                'description' => 'Les meilleurs assistants IA conversationnels : ChatGPT, Claude, Gemini, Copilot et plus.',
                'is_public' => true,
                'user_id' => $admin->id,
            ]
        );
        $this->syncBySlugs($c2, ['chatgpt', 'claude', 'gemini', 'copilot', 'grok', 'mistral-le-chat', 'notion-ai']);

        // Collection 3 : Audio
        $c3 = ToolCollection::updateOrCreate(
            ['slug' => 'outils-de-creation-audio'],
            [
                'name' => 'Outils de création audio',
                'description' => 'Génération de musique, voix, son : Suno, Udio, ElevenLabs, etc.',
                'is_public' => true,
                'user_id' => $admin->id,
            ]
        );
        $this->syncBySlugs($c3, ['suno', 'udio', 'elevenlabs']);
    }

    private function syncBySlugs(ToolCollection $collection, array $slugs): void
    {
        $sync = [];
        $pos = 1;
        $found = 0;

        foreach ($slugs as $slug) {
            $tool = Tool::whereRaw('JSON_UNQUOTE(JSON_EXTRACT(slug, \'$."fr_CA"\')) = ?', [$slug])->first();
            if ($tool) {
                $sync[$tool->id] = ['position' => $pos, 'added_at' => now()];
                $pos++;
                $found++;
            } else {
                $this->command->warn("Slug \"{$slug}\" introuvable.");
            }
        }

        $collection->tools()->sync($sync);
        $this->command->info("Collection \"{$collection->name}\" : {$found} outil(s).");
    }
}
