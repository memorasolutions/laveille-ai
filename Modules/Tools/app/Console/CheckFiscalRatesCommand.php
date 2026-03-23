<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tools\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Modules\Tools\Mail\FiscalRatesReminderMail;

class CheckFiscalRatesCommand extends Command
{
    protected $signature = 'tools:check-fiscal-rates';

    protected $description = 'Envoyer un rappel aux admins pour vérifier les taux fiscaux du simulateur';

    public function handle(): int
    {
        $jsonPath = module_path('Tools', 'resources/data/simulateur-fiscal.json');

        if (! File::exists($jsonPath)) {
            $this->error("Fichier de configuration fiscal introuvable : {$jsonPath}");

            return self::FAILURE;
        }

        $data = json_decode(File::get($jsonPath), true);
        $configYear = $data['meta']['year'] ?? null;
        $lastUpdated = $data['meta']['lastUpdated'] ?? 'Inconnu';
        $currentYear = (int) date('Y');

        if ((int) $configYear === $currentYear) {
            $this->info("Les taux fiscaux sont à jour (année : {$configYear}).");

            return self::SUCCESS;
        }

        $this->info("Année config ({$configYear}) != année courante ({$currentYear}). Envoi du rappel...");

        $recipients = $this->getRecipients();

        if (empty($recipients)) {
            $this->warn('Aucun destinataire trouvé.');

            return self::FAILURE;
        }

        foreach ($recipients as $email) {
            Mail::to($email)->send(new FiscalRatesReminderMail(
                currentYear: $currentYear,
                configYear: $configYear,
                lastUpdated: $lastUpdated,
                jsonPath: $jsonPath
            ));
            $this->info("Email envoyé à : {$email}");
        }

        return self::SUCCESS;
    }

    private function getRecipients(): array
    {
        $emails = [];

        if (class_exists(\Spatie\Permission\Models\Role::class) && class_exists(\App\Models\User::class)) {
            try {
                $admins = \App\Models\User::role('admin')->get();
                foreach ($admins as $admin) {
                    if ($admin->email) {
                        $emails[] = $admin->email;
                    }
                }
            } catch (\Exception $e) {
                // Spatie Permission non configuré ou table absente
            }
        }

        if (empty($emails)) {
            $fallback = config('mail.from.address');
            if ($fallback) {
                $emails[] = $fallback;
            }
        }

        return array_unique($emails);
    }
}
