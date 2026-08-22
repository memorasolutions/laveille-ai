<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\FrontTheme\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Modules\Core\Support\Honeypot;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('fronttheme::contact');
    }

    public function send(Request $request): RedirectResponse|JsonResponse
    {
        $success = function () use ($request) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => __('Votre message a bien été envoyé.')]);
            }

            return back()->with('success', __('Votre message a bien été envoyé.'));
        };

        // Anti-bot - honeypot maison : champ caché 'hp_url' qu'un humain ne remplit jamais.
        // On NE renvoie PLUS sans trace : on met en QUARANTAINE (status='spam') pour que
        // l'humain puisse vérifier l'absence de faux positif, puis on renvoie le succès
        // (silencieux, le bot n'apprend rien) sans envoyer le courriel.
        if (Honeypot::isBot($request)) {
            $this->quarantine([
                'name' => mb_substr((string) $request->input('name', ''), 0, 255),
                'email' => mb_substr((string) $request->input('email', ''), 0, 255),
                'subject' => mb_substr((string) $request->input('subject', ''), 0, 255),
                'message' => (string) $request->input('message', ''),
                'ip_address' => $request->ip(),
            ], 'honeypot');

            return $success();
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10'],
        ]);

        // Tronc commun de l'enregistrement, quelle que soit l'issue (toujours persisté).
        $base = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'ip_address' => $request->ip(),
        ];

        // On collecte TOUS les signaux (sans court-circuit) pour pouvoir tracer la raison.
        $reasons = [];

        // Anti-pourriel de liens SEO : compte les URL (http(s)://, www. ou domaine.tld/chemin).
        // 4 liens ou plus dans le message → signal fort (cas typique du spam de backlinks).
        $urlCount = preg_match_all('~(?:https?://|www\.|\b[a-z0-9][a-z0-9.-]*\.[a-z]{2,}/)\S*~i', (string) $validated['message']);
        if ($urlCount >= 4) {
            $reasons[] = 'liens>=4';
        }

        // Anti-spam en couches (volontairement conservateur : on ne veut bloquer AUCUNE vraie personne).
        $signals = $this->spamSignals($validated, $request);
        foreach ($signals as $signal) {
            $reasons[] = $signal;
        }

        // Signaux « contenu » faillibles : shortener, keyword, allcaps.
        $weakSignals = array_intersect(['shortener', 'keyword', 'allcaps'], $signals);
        $weakCount = count($weakSignals);

        // Spam à haute confiance : honeypot (déjà traité), >=4 liens, time-trap (soumission
        // quasi instantanée = robot), OU au moins 2 signaux « contenu » combinés.
        $hardSpam = $urlCount >= 4
            || in_array('timetrap', $signals, true)
            || $weakCount >= 2;

        // Signal faible isolé : exactement 1 des 3 signaux « contenu ».
        $weak = $weakCount === 1 && ! $hardSpam;

        // Raison lisible (ordre stable) : honeypot et signaux confondus, dédupliqués.
        $reason = implode(',', array_values(array_unique($reasons)));

        // Spam fort → QUARANTAINE consultable, AUCUN courriel, succès silencieux.
        if ($hardSpam) {
            $this->quarantine($base, $reason !== '' ? $reason : 'spam');

            return $success();
        }

        // Sinon : on persiste en 'new' (visible dans la boîte) puis on envoie le courriel.
        // Un signal faible isolé est tracé dans spam_reason et préfixe le sujet « [Spam probable] ».
        $this->quarantine($base, $weak ? $reason : null, 'new');

        $subjectPrefix = $weak ? '[Spam probable] ' : '';

        // Corps enrichi : qui a écrit + comment répondre (l'expéditeur reste l'adresse du site
        // pour la délivrabilité ; le Reply-To pointe vers le visiteur → « Répondre » lui écrit).
        $body = 'Nouveau message reçu via le formulaire de contact de '.config('app.name').'.'."\n\n"
            .'De : '.$validated['name'].' <'.$validated['email'].'>'."\n"
            .'Sujet : '.$validated['subject']."\n"
            .'Date : '.now()->timezone('America/Toronto')->format('Y-m-d H:i').' (Québec)'."\n\n"
            .'Message :'."\n".$validated['message']."\n\n"
            .'- Pour lui répondre, utilisez simplement « Répondre » : votre réponse ira directement à '.$validated['email'].'.';

        // Provenance : on garde l'ADRESSE du domaine (obligatoire pour DMARC/DKIM, jamais l'email
        // du visiteur = usurpation) et on change le NOM affiché en « Nom du visiteur via La veille ».
        $fromName = $validated['name'].' via '.config('app.name');

        Mail::raw($body, function ($message) use ($validated, $fromName, $subjectPrefix) {
            $message->from(config('mail.from.address'), $fromName)
                ->to(config('mail.from.address'))
                ->subject($subjectPrefix.'Contact: '.$validated['subject'])
                ->replyTo($validated['email'], $validated['name']);
        });

        return $success();
    }

    /**
     * Persiste une soumission de contact en base.
     *
     * Défensif : le modèle/la table peuvent être absents dans un contexte de portabilité
     * (module retiré, base non migrée) → on garde le flux fonctionnel sans jamais casser
     * l'envoi du formulaire. tenant_id est nullable : on ne le fournit pas.
     *
     * @param  array<string, mixed>  $base  name, email, subject, message, ip_address.
     * @param  string|null  $reason  Raison lisible des signaux (null si aucun).
     * @param  string  $status  'spam' (quarantaine) ou 'new' (boîte normale).
     */
    private function quarantine(array $base, ?string $reason, string $status = 'spam'): void
    {
        if (! class_exists(ContactMessage::class)) {
            return;
        }

        try {
            ContactMessage::create($base + [
                'status' => $status,
                'spam_reason' => $reason,
            ]);
        } catch (\Throwable) {
            // Persistance best-effort : ne jamais bloquer la réponse au visiteur.
        }
    }

    /**
     * Détecte les signaux de pourriel d'un envoi de contact.
     *
     * Volontairement CONSERVATEUR : chaque signal est concu pour ne pas déclencher
     * sur un message légitime. La décision finale (envoyer / préfixer / rejeter) est
     * prise dans send() en combinant les signaux. Cette méthode est pure et testable.
     *
     * Signaux possibles : 'shortener', 'keyword', 'allcaps', 'timetrap'.
     *
     * @param  array<string, mixed>  $data  Données validées (name, email, subject, message).
     * @return list<string> Liste des signaux détectés.
     */
    private function spamSignals(array $data, Request $request): array
    {
        $signals = [];

        $subject = (string) ($data['subject'] ?? '');
        $message = (string) ($data['message'] ?? '');
        $haystack = $subject."\n".$message;

        // 1) Raccourcisseurs d'URL connus (souvent du spam). On EXCLUT explicitement nos propres
        //    domaines de raccourcissement pour ne jamais pénaliser un partage légitime.
        $ownShorteners = ['lurl.ca', 'veille.la', '1lien.ca', 'unlien.ca', 'go3.ca'];
        $spamShorteners = [
            'url.in.th', 'bit.ly', 'tinyurl.com', 't.co', 'goo.gl', 'ow.ly', 'is.gd',
            'buff.ly', 'cutt.ly', 'rebrand.ly', 'rb.gy', 'shorturl.at', 't.ly', 'soo.gd',
        ];
        $lowerHaystack = mb_strtolower($haystack);
        // On retire d'abord nos propres domaines pour qu'ils ne déclenchent jamais le signal.
        $scanHaystack = str_ireplace($ownShorteners, ' ', $lowerHaystack);
        foreach ($spamShorteners as $domain) {
            // Frontière de domaine : précédé d'un non-alphanum (ou début), suivi d'un non-alphanum (ou fin).
            if (preg_match('~(?:^|[^a-z0-9.])'.preg_quote($domain, '~').'(?:[^a-z0-9]|$)~i', $scanHaystack) === 1) {
                $signals[] = 'shortener';
                break;
            }
        }

        // 2) Mots-clés de pourriel (frontières de mots, insensible à la casse) + motif « montant géant ».
        $keywords = [
            'jackpot', 'casino', 'lottery', 'lotto', 'viagra', 'cialis', 'forex', 'bitcoin',
            'crypto', 'giveaway', 'prize', 'winner', 'loan', 'mortgage', 'sexy', 'escort', 'porn',
        ];
        $keywordHit = false;
        foreach ($keywords as $kw) {
            if (preg_match('~\b'.preg_quote($kw, '~').'\b~iu', $haystack) === 1) {
                $keywordHit = true;
                break;
            }
        }
        // Montant en dollars géant, ex. $27,000,000.
        if (! $keywordHit && preg_match('~\$\d{1,3}(?:,\d{3})+~', $haystack) === 1) {
            $keywordHit = true;
        }
        if ($keywordHit) {
            $signals[] = 'keyword';
        }

        // 3) Tout en MAJUSCULES : sujet OU message > 15 caractères ET >= 60 % des LETTRES en majuscules.
        if ($this->isAllCaps($subject) || $this->isAllCaps($message)) {
            $signals[] = 'allcaps';
        }

        // 4) Time-trap : formulaire soumis trop vite (< 3 s). Permissif si le champ est absent/incohérent.
        $ts = $request->input('form_ts');
        if ($ts !== null && is_numeric($ts)) {
            $elapsed = time() - (int) $ts;
            if ($elapsed >= 0 && $elapsed <= 3) {
                $signals[] = 'timetrap';
            }
        }

        return array_values(array_unique($signals));
    }

    /**
     * Vrai si le texte fait plus de 15 caractères ET qu'au moins 60 % de ses LETTRES sont majuscules.
     */
    private function isAllCaps(string $text): bool
    {
        if (mb_strlen($text) <= 15) {
            return false;
        }

        // On ne compte que les lettres (les chiffres/ponctuation/accents non-cas sont ignorés).
        $letters = preg_match_all('~\p{L}~u', $text, $allLetters) ? $allLetters[0] : [];
        $letterCount = count($letters);
        if ($letterCount === 0) {
            return false;
        }

        $upperCount = preg_match_all('~\p{Lu}~u', $text);

        return ($upperCount / $letterCount) >= 0.60;
    }
}
