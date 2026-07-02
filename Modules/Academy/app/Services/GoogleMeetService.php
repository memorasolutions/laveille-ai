<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ACTION: Auto-création d'un lien Google Meet via l'API Google Calendar
 *         (phase 2 des séances en direct — voir LiveSessionsManager).
 * MCP: SELF (< 5 lignes de logique métier par méthode publique, service serveur-à-serveur).
 * RAISON: Le formateur ne veut plus copier-coller manuellement le lien Meet.
 *
 * CONFIGURATION GOOGLE CLOUD CONSOLE REQUISE (à faire une seule fois côté client) :
 *   1. Créer/choisir un projet dans https://console.cloud.google.com.
 *   2. Activer l'API « Google Calendar API » (menu API et services > Bibliothèque).
 *   3. Créer un COMPTE DE SERVICE (IAM et administration > Comptes de service),
 *      puis générer une CLÉ JSON pour ce compte (onglet Clés > Ajouter une clé > JSON).
 *   4. Si le calendrier appartient à un compte Google Workspace (organisation) :
 *      activer la DÉLÉGATION DOMAIN-WIDE sur le compte de service (case à cocher
 *      dans la console + autoriser le « Client ID » du compte de service dans
 *      l'admin Google Workspace > Sécurité > Contrôle des API > Délégation
 *      d'autorité dans tout le domaine, avec le scope
 *      https://www.googleapis.com/auth/calendar.events) ET renseigner
 *      GOOGLE_MEET_IMPERSONATE_USER (l'adresse du compte Workspace au nom
 *      duquel les événements sont créés — l'API Meet exige un compte réel,
 *      pas un compte de service seul).
 *   5. Coller le contenu du fichier JSON téléchargé dans la variable d'env
 *      GOOGLE_MEET_SERVICE_ACCOUNT_JSON (JSON en une ligne, JAMAIS commité)
 *      OU pointer GOOGLE_MEET_SERVICE_ACCOUNT_JSON_PATH vers un chemin de
 *      fichier sur le serveur (hors du dépôt git).
 *   6. Poser ACADEMY_GOOGLE_MEET_AUTOCREATE_ENABLED=true dans le .env pour
 *      activer la fonctionnalité (défaut false = comportement inchangé,
 *      champ manuel).
 *
 * DÉGRADATION PROPRE (même esprit que Authors\Services\TurnstileVerificationService) :
 * isConfigured() ne lève jamais d'exception ; createMeetLink() catch tout
 * \Throwable, journalise, et retourne null — jamais de casse du flux de
 * création manuelle de séance en repli.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendarService;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\ConferenceSolutionKey;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleMeetService
{
    /** Calendrier cible pour la création d'événements (« primary » = calendrier principal de l'utilisateur emprunté). */
    private const CALENDAR_ID = 'primary';

    private const SCOPE = GoogleCalendarService::CALENDAR_EVENTS;

    /**
     * Vrai si le drapeau maître est actif ET que des identifiants Google
     * exploitables sont configurés. Ne lève JAMAIS d'exception.
     */
    public function isConfigured(): bool
    {
        if (! (bool) config('academy.google_meet_autocreate_enabled', false)) {
            return false;
        }

        return $this->credentialsPayload() !== null;
    }

    /**
     * Crée un événement Google Calendar avec conférence Meet auto-générée et
     * retourne l'URL de jonction. Retourne NULL proprement (jamais d'exception)
     * si non configuré ou si l'appel échoue — le formateur retombe alors sur
     * la saisie manuelle du lien (comportement actuel inchangé).
     */
    public function createMeetLink(string $title, \DateTimeInterface $startsAt, \DateTimeInterface $endsAt): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $client = $this->buildClient();
            if ($client === null) {
                return null;
            }

            $service = new GoogleCalendarService($client);

            $event = new Event([
                'summary' => $title,
                'start'   => new EventDateTime(['dateTime' => $startsAt->format(\DateTimeInterface::RFC3339), 'timeZone' => 'UTC']),
                'end'     => new EventDateTime(['dateTime' => $endsAt->format(\DateTimeInterface::RFC3339), 'timeZone' => 'UTC']),
                'conferenceData' => new ConferenceData([
                    'createRequest' => new CreateConferenceRequest([
                        'requestId'             => (string) \Illuminate\Support\Str::uuid(),
                        'conferenceSolutionKey'  => new ConferenceSolutionKey(['type' => 'hangoutsMeet']),
                    ]),
                ]),
            ]);

            $created = $service->events->insert(self::CALENDAR_ID, $event, ['conferenceDataVersion' => 1]);

            $joinUrl = $created->getHangoutLink();

            if (empty($joinUrl)) {
                Log::channel('daily')->warning('academy.google_meet.no_link_returned', ['title' => $title]);

                return null;
            }

            return $joinUrl;
        } catch (Throwable $e) {
            Log::channel('daily')->error('academy.google_meet.create_failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Construit le client Google authentifié (compte de service + délégation
     * domain-wide optionnelle). Retourne null proprement si la construction
     * échoue (clé invalide, etc.) — jamais d'exception propagée à l'appelant.
     */
    private function buildClient(): ?GoogleClient
    {
        $credentials = $this->credentialsPayload();
        if ($credentials === null) {
            return null;
        }

        try {
            $client = new GoogleClient();
            $client->setApplicationName(config('app.name', 'Academy'));
            $client->setAuthConfig($credentials);
            $client->setScopes([self::SCOPE]);

            // Délégation domain-wide : le compte de service agit AU NOM d'un
            // vrai compte Google Workspace (requis pour que l'API génère un
            // lien Meet — un compte de service seul ne peut pas créer de Meet).
            $impersonate = config('academy.google_meet_impersonate_user');
            if (! empty($impersonate)) {
                $client->setSubject($impersonate);
            }

            return $client;
        } catch (Throwable $e) {
            Log::channel('daily')->error('academy.google_meet.client_build_failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Résout les identifiants du compte de service sous forme de tableau
     * décodé (format attendu par Google\Client::setAuthConfig). Supporte
     * soit le JSON brut en variable d'env, soit un chemin de fichier.
     * Retourne null si absent/invalide (jamais d'exception).
     *
     * @return array<string, mixed>|null
     */
    private function credentialsPayload(): ?array
    {
        $inlineJson = config('academy.google_meet_service_account_json');
        if (! empty($inlineJson)) {
            $decoded = json_decode((string) $inlineJson, true);

            return is_array($decoded) ? $decoded : null;
        }

        $path = config('academy.google_meet_service_account_json_path');
        if (! empty($path) && is_string($path) && is_file($path) && is_readable($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}
