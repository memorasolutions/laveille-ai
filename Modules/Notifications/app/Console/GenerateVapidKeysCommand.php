<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Notifications\Console;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;
use Modules\Settings\Models\Setting;

class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'webpush:vapid';

    protected $description = 'Generate VAPID keys for web push notifications';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->info('VAPID keys generated successfully:');
        $this->line('Public Key: '.$keys['publicKey']);
        $this->line('Private Key: '.$keys['privateKey']);

        $this->updateEnvFile($keys);
        $this->updateSettings($keys);

        $this->info('Keys saved to .env and database settings.');

        return Command::SUCCESS;
    }

    private function updateEnvFile(array $keys): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);

        $replacements = [
            'VAPID_PUBLIC_KEY' => $keys['publicKey'],
            'VAPID_PRIVATE_KEY' => $keys['privateKey'],
        ];

        foreach ($replacements as $key => $value) {
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $content);
    }

    private function updateSettings(array $keys): void
    {
        Setting::updateOrCreate(
            ['key' => 'push.vapid_public_key'],
            ['group' => 'push', 'value' => $keys['publicKey'], 'type' => 'string', 'description' => 'Clé publique VAPID']
        );

        Setting::updateOrCreate(
            ['key' => 'push.vapid_private_key'],
            ['group' => 'push', 'value' => $keys['privateKey'], 'type' => 'string', 'description' => 'Clé privée VAPID']
        );
    }
}
