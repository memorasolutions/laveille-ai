<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\FrontTheme\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

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
        // Rejet SILENCIEUX (on renvoie le succès, le bot n'apprend rien) sans envoyer le courriel.
        if ($request->filled('hp_url')) {
            return $success();
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10'],
        ]);

        // Anti-pourriel de liens SEO : compte les URL (http(s)://, www. ou domaine.tld/chemin).
        // 4 liens ou plus dans le message → rejet silencieux (cas typique du spam de backlinks).
        $urlCount = preg_match_all('~(?:https?://|www\.|\b[a-z0-9][a-z0-9.-]*\.[a-z]{2,}/)\S*~i', (string) $validated['message']);
        if ($urlCount >= 4) {
            return $success();
        }

        // Anti-spam en couches (volontairement conservateur : on ne veut bloquer AUCUNE vraie personne).
        $signals = $this->spamSignals($validated, $request);

        // Signaux à très haute confiance (un humain ne les déclenche jamais) → rejet SILENCIEUX.
        // 'timetrap' = soumission quasi instantanée = robot.
        if (in_array('timetrap', $signals, true)) {
            return $success();
        }

        // Signaux « contenu » faillibles : shortener, keyword, allcaps.
        // Au moins 2 sur 3 → haute confiance combinée → rejet silencieux.
        // Exactement 1 → on N'EN PERD PAS : on envoie quand même mais on préfixe « [Spam probable] »
        // pour que l'humain tranche. 0 → envoi normal.
        $weakSignals = array_intersect(['shortener', 'keyword', 'allcaps'], $signals);
        $weakCount = count($weakSignals);
        if ($weakCount >= 2) {
            return $success();
        }

        $subjectPrefix = $weakCount === 1 ? '[Spam probable] ' : '';

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
