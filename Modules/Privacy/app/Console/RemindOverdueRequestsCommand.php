<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Privacy\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Privacy\Models\RightsRequest;

class RemindOverdueRequestsCommand extends Command
{
    protected $signature = 'privacy:remind-overdue-requests {--dry-run : Affiche les demandes concernees sans envoyer le courriel}';

    protected $description = 'Rappelle au DPO les demandes de droits non traitees qui approchent le delai de reponse de 30 jours (Loi 25 / RGPD).';

    /**
     * Seuil d'approche du delai promis dans la politique de confidentialite
     * (rights.response_delay_days = 30). On alerte a 25 jours pour laisser
     * 5 jours de marge de traitement avant l'echeance.
     */
    private const THRESHOLD_DAYS = 25;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $overdueRequests = RightsRequest::query()
            ->where('status', '!=', 'completed')
            ->where('created_at', '<=', now()->subDays(self::THRESHOLD_DAYS))
            ->whereNull('reminded_at')
            ->orderBy('created_at')
            ->get();

        if ($overdueRequests->isEmpty()) {
            $this->info('Aucune demande de droits en approche du delai de 30 jours.');

            return self::SUCCESS;
        }

        $this->table(
            ['Reference', 'Type', 'Creee le', 'Echeance'],
            $overdueRequests->map(fn (RightsRequest $r) => [
                $r->reference,
                $r->request_type,
                $r->created_at->format('Y-m-d'),
                $r->deadline_at->format('Y-m-d'),
            ])->toArray()
        );

        if ($dryRun) {
            $this->info("Mode dry-run : {$overdueRequests->count()} demande(s) seraient rappelees au DPO, aucun courriel envoye.");

            return self::SUCCESS;
        }

        $dpoEmail = config('privacy.rights.notification_email');

        if (!$dpoEmail) {
            $this->warn('Aucune adresse DPO configuree (privacy.rights.notification_email) : rappel annule.');

            return self::SUCCESS;
        }

        $body = "Rappel : demandes d'exercice de droits en approche du delai de reponse de 30 jours\n\n";
        foreach ($overdueRequests as $request) {
            $joursRestants = (int) now()->diffInDays($request->deadline_at, false);
            $body .= "- {$request->reference} ({$request->request_type}) de {$request->name} <{$request->email}> "
                ."- creee le {$request->created_at->format('Y-m-d')}, echeance le {$request->deadline_at->format('Y-m-d')} "
                ."(dans {$joursRestants} jour(s))\n";
        }

        try {
            Mail::raw($body, function ($message) use ($dpoEmail, $overdueRequests) {
                $message->to($dpoEmail)
                    ->subject("[Droits] {$overdueRequests->count()} demande(s) en approche du delai de 30 jours");
            });
        } catch (\Exception $e) {
            Log::error('Overdue rights request reminder failed: '.$e->getMessage());
            $this->error('Echec de l\'envoi du courriel de rappel : '.$e->getMessage());

            return self::FAILURE;
        }

        // Anti-spam idempotent : une fois le rappel envoye, on ne renotifie plus jamais
        // la meme demande (elle sera de toute facon traitee ou visible dans le backoffice).
        RightsRequest::whereIn('id', $overdueRequests->pluck('id'))->update(['reminded_at' => now()]);

        $this->info("Rappel envoye au DPO pour {$overdueRequests->count()} demande(s).");

        return self::SUCCESS;
    }
}
