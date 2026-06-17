<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Newsletter\Models\NewsletterEvent;
use Modules\Newsletter\Models\NewsletterIssue;

/**
 * Page ADMIN « Statistiques newsletter » — LECTURE SEULE.
 *
 * Aucune écriture / suppression : on ne fait que lire et agréger
 * `newsletter_events`, `newsletter_issues` et `newsletter_issue_sends`.
 *
 * Deux niveaux :
 *  1. GLOBAL (cartes) : totaux par type d'événement NORMALISÉ + ouvertures/clics uniques.
 *  2. PAR NUMÉRO (tableau) : attribution simple et robuste des ouvertures/clics
 *     uniques à chaque numéro envoyé, par appartenance (issue_sends) + fenêtre temporelle.
 */
class NewsletterStatsController extends Controller
{
    /**
     * Familles d'événements normalisées (Brevo envoie des libellés variables :
     * opened/unique_opened, click/clicks/clicked, hard_bounce/soft_bounce, etc.).
     */
    private const OPEN_EVENTS = ['opened', 'unique_opened'];

    private const CLICK_EVENTS = ['click', 'clicks', 'clicked'];

    private const BOUNCE_EVENTS = ['hard_bounce', 'soft_bounce'];

    private const SPAM_EVENTS = ['spam', 'complaint'];

    public function index()
    {
        // ===================================================================
        // 1) GLOBAL — totaux par type normalisé (tous les événements, all-time)
        // ===================================================================

        // Une seule requête : compte brut par libellé d'événement.
        $rawCounts = NewsletterEvent::query()
            ->select('event', DB::raw('COUNT(*) as c'))
            ->groupBy('event')
            ->pluck('c', 'event'); // Collection : libellé => total

        $sumOf = static fn (array $types): int => collect($types)
            ->sum(static fn (string $t): int => (int) $rawCounts->get($t, 0));

        $global = [
            'total_events' => (int) $rawCounts->sum(),
            'opens'        => $sumOf(self::OPEN_EVENTS),
            'clicks'       => $sumOf(self::CLICK_EVENTS),
            'bounces'      => $sumOf(self::BOUNCE_EVENTS),
            'unsubscribes' => (int) $rawCounts->get('unsubscribed', 0),
            'spam'         => $sumOf(self::SPAM_EVENTS),
            // Ouvertures / clics UNIQUES = COUNT(DISTINCT email) sur la famille.
            'unique_opens'  => (int) NewsletterEvent::query()
                ->whereIn('event', self::OPEN_EVENTS)
                ->distinct()
                ->count('email'),
            'unique_clicks' => (int) NewsletterEvent::query()
                ->whereIn('event', self::CLICK_EVENTS)
                ->distinct()
                ->count('email'),
        ];

        // ===================================================================
        // 2) PAR NUMÉRO — attribution par appartenance + fenêtre temporelle
        // ===================================================================
        //
        // Logique d'attribution (simple et robuste) pour CHAQUE numéro envoyé :
        //  - destinataires = COUNT(DISTINCT subscriber_email) dans issue_sends.
        //  - un destinataire compte comme « ouverture unique » s'il a AU MOINS une
        //    ouverture dont la date (occurred_at, sinon created_at) tombe dans la
        //    fenêtre [date d'envoi du numéro ; date d'envoi du PROCHAIN numéro reçu
        //    par CE courriel[. À défaut de prochain numéro reçu : +14 jours.
        //  - idem pour les clics uniques.
        //
        // Cette fenêtre évite qu'une même ouverture soit attribuée à deux numéros :
        // elle est rattachée au dernier numéro reçu avant l'événement.

        // 2a. Numéros envoyés, ordre CHRONOLOGIQUE (asc) pour calculer les bornes,
        //     puis affichés du plus récent au plus ancien.
        $issues = NewsletterIssue::query()
            ->whereNotNull('sent_at')
            ->orderBy('sent_at')
            ->get(['id', 'year', 'week_number', 'subject', 'sent_at']);

        $rows = [];

        if ($issues->isNotEmpty()) {
            $issueIds   = $issues->pluck('id')->all();
            $sentAtById = $issues->pluck('sent_at', 'id'); // id => Carbon

            // 2b. Appartenance : destinataires par numéro (1 requête).
            $sends = DB::table('newsletter_issue_sends')
                ->whereIn('issue_id', $issueIds)
                ->select('issue_id', 'subscriber_email')
                ->get();

            $recipientsByIssue = []; // issue_id => [email => true]  (DISTINCT naturel)
            $issuesByEmail     = []; // email => [timestamp d'envoi, ...]
            foreach ($sends as $s) {
                $sentAt = $sentAtById[$s->issue_id] ?? null;
                if ($sentAt === null) {
                    continue;
                }
                $recipientsByIssue[$s->issue_id][$s->subscriber_email] = true;
                $issuesByEmail[$s->subscriber_email][] = $sentAt->getTimestamp();
            }

            // Trie les envois reçus par courriel (asc) → la borne de fin = 1er envoi
            // strictement postérieur au numéro courant.
            foreach ($issuesByEmail as &$timestamps) {
                sort($timestamps);
            }
            unset($timestamps);

            // 2c. Événements d'engagement (ouvertures + clics) regroupés par courriel.
            //     À l'échelle actuelle (≤ quelques centaines), un chargement mémoire
            //     après 1 requête est largement suffisant et évite tout N+1.
            $opensByEmail  = []; // email => [timestamp, ...]
            $clicksByEmail = [];

            NewsletterEvent::query()
                ->whereIn('event', array_merge(self::OPEN_EVENTS, self::CLICK_EVENTS))
                ->get(['email', 'event', 'occurred_at', 'created_at'])
                ->each(function (NewsletterEvent $ev) use (&$opensByEmail, &$clicksByEmail): void {
                    // Date réelle de l'événement Brevo, sinon date d'insertion en base.
                    $when = $ev->occurred_at ?? $ev->created_at;
                    if ($when === null) {
                        return;
                    }
                    $ts = $when->getTimestamp();
                    if (in_array($ev->event, self::OPEN_EVENTS, true)) {
                        $opensByEmail[$ev->email][] = $ts;
                    } else {
                        $clicksByEmail[$ev->email][] = $ts;
                    }
                });

            // 2d. Calcul par numéro.
            foreach ($issues as $issue) {
                $startTs    = $sentAtById[$issue->id]->getTimestamp();
                $recipients = array_keys($recipientsByIssue[$issue->id] ?? []);

                $recipientCount = count($recipients);
                $uniqueOpens    = 0;
                $uniqueClicks   = 0;

                foreach ($recipients as $email) {
                    // Borne de fin : 1er envoi reçu par CE courriel après le numéro courant.
                    $endTs = null;
                    foreach ($issuesByEmail[$email] ?? [] as $sentTs) {
                        if ($sentTs > $startTs) {
                            $endTs = $sentTs; // liste triée asc → premier = prochain envoi
                            break;
                        }
                    }
                    if ($endTs === null) {
                        $endTs = $startTs + 14 * 86400; // pas de prochain envoi → +14 jours
                    }

                    // Au moins une ouverture dans [start, end[ ?
                    foreach ($opensByEmail[$email] ?? [] as $ts) {
                        if ($ts >= $startTs && $ts < $endTs) {
                            $uniqueOpens++;
                            break;
                        }
                    }
                    // Au moins un clic dans [start, end[ ?
                    foreach ($clicksByEmail[$email] ?? [] as $ts) {
                        if ($ts >= $startTs && $ts < $endTs) {
                            $uniqueClicks++;
                            break;
                        }
                    }
                }

                $rows[] = [
                    'label'         => $issue->year . '-S' . str_pad((string) $issue->week_number, 2, '0', STR_PAD_LEFT),
                    'subject'       => $issue->subject,
                    'sent_at'       => $sentAtById[$issue->id], // Carbon (affiché en America/Toronto)
                    'recipients'    => $recipientCount,
                    'unique_opens'  => $uniqueOpens,
                    'open_rate'     => $recipientCount > 0 ? round($uniqueOpens / $recipientCount * 100, 1) : 0.0,
                    'unique_clicks' => $uniqueClicks,
                    'ctr'           => $recipientCount > 0 ? round($uniqueClicks / $recipientCount * 100, 1) : 0.0,
                ];
            }

            // Affichage : du plus récent au plus ancien.
            $rows = array_reverse($rows);
        }

        return view('newsletter::admin.stats', [
            'global'    => $global,
            'rows'      => $rows,
            'hasEvents' => $global['total_events'] > 0,
        ]);
    }
}
