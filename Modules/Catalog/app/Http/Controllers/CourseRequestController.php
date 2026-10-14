<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Modules\Catalog\Http\Requests\StoreCourseRequest;
use Modules\Catalog\Models\CourseRequest;
use Modules\Core\Support\DisposableEmail;
use Modules\Core\Support\Honeypot;

/**
 * Formulaire public « demander un cours personnalisé » (vitrine /cours).
 *
 * Anti-spam EN COUCHES, volontairement conservateur (ne jamais bloquer une vraie personne) :
 * pot de miel + piège temporel + URL dans le nom + excès de liens + courriel jetable + Turnstile
 * (dormant tant que la clé est absente) + limiteur de débit sur la route. Le même visiteur voit
 * toujours un succès (le robot n'apprend rien) ; une soumission suspecte est mise en QUARANTAINE
 * (status=spam) plutôt que jetée - calqué sur le formulaire de contact du site (DRY).
 *
 * Données personnelles collectées : la Loi 25 impose une finalité claire et le consentement ;
 * ils sont portés par la vue et par la case « consentement » validée en amont.
 */
class CourseRequestController extends Controller
{
    public function create(): View
    {
        abort_unless((bool) config('catalog.request_enabled', true), 404);

        return view('catalog::public.request');
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        abort_unless((bool) config('catalog.request_enabled', true), 404);

        $success = fn (): RedirectResponse => back()->with(
            'success',
            'Votre demande a bien été envoyée. Nous vous répondrons rapidement.'
        );

        // Pot de miel : un humain ne remplit jamais ce champ caché. Quarantaine + succès silencieux.
        if (Honeypot::isBot($request)) {
            $this->persist($this->base($request), 'spam', 'honeypot');

            return $success();
        }

        $validated = $request->validated();
        $reasons = [];

        // Piège temporel : la vraie vue rend TOUJOURS form_rendered_at = time(). Son absence, une
        // valeur non numérique, une valeur nulle ou négative (ex. un robot qui envoie « 0 » pour
        // feindre un délai énorme), ou un délai de moins de 3 secondes trahissent un robot.
        $renderedAt = $request->input('form_rendered_at');
        if (! is_numeric($renderedAt) || (int) $renderedAt <= 0 || (time() - (int) $renderedAt) < 3) {
            $reasons[] = 'timetrap';
        }

        // URL (avec chemin) dans le NOM : un vrai nom n'en contient jamais.
        $urlPattern = '~(?:https?://|www\.|\b[a-z0-9][a-z0-9.-]*\.[a-z]{2,}/)\S*~i';
        if (preg_match($urlPattern, (string) $validated['nom']) === 1) {
            $reasons[] = 'url_in_name';
        }

        // Excès de liens dans le message (>= 4) : signature du pourriel de référencement.
        if (preg_match_all($urlPattern, (string) $validated['message']) >= 4) {
            $reasons[] = 'links';
        }

        // Courriel jetable (source unique Core, appel statique).
        if (DisposableEmail::isDisposable((string) $validated['courriel'])) {
            $reasons[] = 'disposable_email';
        }

        // Turnstile : gardé (le module Authors porte le service ; dormant tant que la clé manque,
        // fail-open pour ne jamais bloquer une conversion). Un échec AVÉRÉ compte comme signal fort.
        if (class_exists(\Modules\Authors\Services\TurnstileVerificationService::class)) {
            $turnstile = app(\Modules\Authors\Services\TurnstileVerificationService::class);
            if (! $turnstile->verify($request->input('cf-turnstile-response'), $request->ip())) {
                $reasons[] = 'turnstile';
            }
        }

        // Spam fort → quarantaine, aucun courriel, succès silencieux.
        if ($reasons !== []) {
            $this->persist($this->base($request), 'spam', implode(',', $reasons));

            return $success();
        }

        // Demande légitime : persistée en 'new' puis notification au fondateur. On garde les deux
        // issues : la base est la trace durable, le courriel la livraison immédiate. Si les DEUX
        // échouent, la demande serait perdue en silence derrière un faux succès - on le journalise
        // en CRITIQUE pour qu'une perte soit toujours visible (jamais une perte silencieuse).
        $persisted = $this->persist($this->base($request), 'new', null);
        $notified = $this->notify($validated);

        if (! $persisted && ! $notified) {
            Log::channel('daily')->critical('catalog.course_request.perte_possible', [
                'courriel' => $validated['courriel'],
                'sujet' => $validated['sujet'],
            ]);
        }

        return $success();
    }

    /**
     * Tronc commun persisté, quelle que soit l'issue.
     *
     * @return array<string, mixed>
     */
    private function base(StoreCourseRequest $request): array
    {
        $v = $request->validated();

        return [
            'nom' => $v['nom'],
            'courriel' => $v['courriel'],
            'organisation' => $v['organisation'] ?? null,
            'sujet' => $v['sujet'],
            'heures' => $v['heures'] ?? null,
            'niveau' => $v['niveau'] ?? null,
            'participants' => $v['participants'] ?? null,
            'message' => $v['message'],
            'ip_address' => $request->ip(),
        ];
    }

    /**
     * Persistance best-effort : ne jamais bloquer la réponse au visiteur (portabilité : la table
     * ou le modèle peuvent être absents si le module est retiré). Retourne true si la ligne a bien
     * été écrite. Un échec est JOURNALISÉ (jamais avalé en silence) pour qu'une perte soit visible.
     *
     * @param  array<string, mixed>  $base
     */
    private function persist(array $base, string $status, ?string $reason): bool
    {
        if (! class_exists(CourseRequest::class)) {
            return false;
        }

        try {
            CourseRequest::create($base + ['status' => $status, 'spam_reason' => $reason]);

            return true;
        } catch (\Throwable $e) {
            // La persistance ne doit jamais empêcher la réponse, mais l'échec est tracé.
            Log::channel('daily')->error('catalog.course_request.persist_echec', [
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Courriel de notification au fondateur, par le mailer transactionnel 'workspace'
     * (jamais Brevo, réservé à l'infolettre). From = adresse du domaine (DMARC/DKIM) ;
     * Reply-To = le visiteur, pour qu'un simple « Répondre » lui écrive.
     *
     * Retourne true si le courriel a bien été remis au mailer ; un échec est JOURNALISÉ.
     *
     * @param  array<string, mixed>  $v
     */
    private function notify(array $v): bool
    {
        // Défense en profondeur contre l'injection d'en-têtes : on retire tout retour à la ligne
        // des valeurs qui alimentent le nom d'expéditeur et le sujet (Symfony Mailer les rejette
        // déjà, mais on ne compte pas sur une seule couche pour une donnée venue d'un visiteur).
        $nom = trim(str_replace(["\r", "\n"], ' ', (string) $v['nom']));
        $sujet = trim(str_replace(["\r", "\n"], ' ', (string) $v['sujet']));

        $date = now()->timezone('America/Toronto')->format('Y-m-d H:i').' (Québec)';
        $corps = 'Nouvelle demande de cours personnalisé via '.config('app.name').'.'."\n\n"
            .'Nom : '.$nom."\n"
            .'Courriel : '.$v['courriel']."\n"
            .'Organisation : '.($v['organisation'] ?? 'non précisée')."\n"
            .'Sujet : '.$sujet."\n"
            .'Durée souhaitée : '.(isset($v['heures']) ? $v['heures'].' h' : 'non précisée')."\n"
            .'Niveau : '.($v['niveau'] ?? 'non précisé')."\n"
            .'Participants : '.($v['participants'] ?? 'non précisé')."\n"
            .'Date : '.$date."\n\n"
            .'Message :'."\n".$v['message']."\n\n"
            .'- Pour répondre, utilisez « Répondre » : votre réponse ira directement à '.$v['courriel'].'.';

        $fromName = $nom.' via '.config('app.name');

        try {
            Mail::mailer('workspace')->raw($corps, function ($message) use ($v, $nom, $sujet, $fromName) {
                $message->from(config('mail.from.address'), $fromName)
                    ->to(config('mail.from.address'))
                    ->subject('Demande de cours sur mesure : '.$sujet)
                    ->replyTo($v['courriel'], $nom);
            });

            return true;
        } catch (\Throwable $e) {
            // La demande est déjà persistée en base : un échec d'envoi ne doit pas casser la réponse,
            // mais il est tracé pour que le fondateur n'ignore pas une notification perdue.
            Log::channel('daily')->error('catalog.course_request.notify_echec', [
                'courriel' => $v['courriel'],
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
