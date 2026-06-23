<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V5-c - SOURCE UNIQUE (DRY) des notifications courriel d'activité de l'Académie
 * (parité Moodle). Toute notification PASSE par la méthode privée send() : c'est
 * le SEUL point d'envoi. Trois gardes y sont appliquées dans l'ordre :
 *
 *   1. INTERRUPTEUR MAITRE  - config('academy.notifications.enabled') (défaut FALSE).
 *      Tant qu'il est faux, RIEN n'est envoyé : la notification est journalisée
 *      (log info) mais jamais transmise à Brevo. Évite tout envoi prématuré pendant
 *      que l'Académie est « en construction ».
 *   2. PRÉFÉRENCE utilisateur - opt-in/opt-out par type (table dédiée + défaut config).
 *   3. DÉDOUBLONNAGE (optionnel, rappels) - clé d'unicité via academy_notification_logs.
 *
 * SCOPE STRICT : chaque méthode publique ne cible que des destinataires pertinents
 * (inscrits actifs du cours, auteur du sujet, étudiant concerné), jamais en masse.
 * ZÉRO exception propagée : un échec d'envoi ne casse jamais le flux appelant.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Modules\Academy\Models\Announcement;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\ForumPost;
use Modules\Academy\Models\ForumTopic;
use Modules\Academy\Models\NotificationLog;
use Modules\Academy\Models\NotificationPreference;
use Modules\Newsletter\Services\BrevoService;

final class AcademyNotificationService
{
    public const TYPE_ANNOUNCEMENT     = 'announcement';
    public const TYPE_FORUM_REPLY      = 'forum_reply';
    public const TYPE_GRADED           = 'graded';
    public const TYPE_COURSE_COMPLETED = 'course_completed';
    public const TYPE_DUE_REMINDER     = 'due_reminder';

    /** Tous les types gérables par l'utilisateur (ordre d'affichage de la page préférences). */
    public const TYPES = [
        self::TYPE_ANNOUNCEMENT,
        self::TYPE_FORUM_REPLY,
        self::TYPE_GRADED,
        self::TYPE_COURSE_COMPLETED,
        self::TYPE_DUE_REMINDER,
    ];

    public function __construct(private readonly BrevoService $brevo) {}

    // ─────────────────────────────────────────────────────────────────────────────
    // INTERRUPTEUR MAITRE + PRÉFÉRENCES
    // ─────────────────────────────────────────────────────────────────────────────

    /** Interrupteur maître global (défaut FALSE). Aucun envoi possible s'il est faux. */
    public function isMasterEnabled(): bool
    {
        return (bool) config('academy.notifications.enabled', false);
    }

    /** Préférence par défaut d'un type (config), utilisée si l'utilisateur n'a rien choisi. */
    public function defaultFor(string $type): bool
    {
        return (bool) config('academy.notifications.defaults.' . $type, true);
    }

    /** Vrai si l'utilisateur reçoit ce type (sa préférence explicite, sinon le défaut config). */
    public function isEnabledFor(User $user, string $type): bool
    {
        $pref = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->first();

        return $pref !== null ? (bool) $pref->enabled : $this->defaultFor($type);
    }

    /**
     * Préférences effectives de l'utilisateur pour TOUS les types (pour la page de réglages).
     *
     * @return array<string, bool>
     */
    public function preferencesFor(User $user): array
    {
        $explicit = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->pluck('enabled', 'type');

        $out = [];
        foreach (self::TYPES as $type) {
            $out[$type] = $explicit->has($type) ? (bool) $explicit->get($type) : $this->defaultFor($type);
        }

        return $out;
    }

    /** Enregistre (upsert) la préférence d'un type pour un utilisateur. */
    public function setPreference(User $user, string $type, bool $enabled): void
    {
        if (! in_array($type, self::TYPES, true)) {
            return;
        }

        NotificationPreference::updateOrCreate(
            ['user_id' => $user->id, 'type' => $type],
            ['enabled' => $enabled],
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // NOTIFICATIONS ÉVÉNEMENTIELLES (immédiates)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Nouvelle annonce publiée -> tous les inscrits ACTIFS du cours.
     * Retourne le nombre de courriels réellement envoyés (0 si interrupteur off).
     *
     * Préférence : préchargée en UNE requête (anti-N+1) puis vérifiée localement
     * avant l'appel send() afin d'éviter N SELECT sur des cours à grande audience.
     */
    public function announcementPublished(Announcement $announcement): int
    {
        if (! $this->isMasterEnabled()) {
            return 0;
        }

        $course = $announcement->course;
        if ($course === null) {
            return 0;
        }

        $userIds = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return 0;
        }

        // Anti-N+1 : toutes les préférences « announcement » en UNE requête.
        $prefMap        = NotificationPreference::whereIn('user_id', $userIds)
            ->where('type', self::TYPE_ANNOUNCEMENT)
            ->get()
            ->keyBy('user_id');
        $defaultEnabled = $this->defaultFor(self::TYPE_ANNOUNCEMENT);

        $users = User::whereIn('id', $userIds)->get();

        $sent    = 0;
        $data    = [
            'course'       => $course,
            'announcement' => $announcement,
            'bodyHtml'     => $announcement->renderedBody(),
            'ctaUrl'       => $this->courseUrl($course),
            'ctaLabel'     => 'Ouvrir le cours',
        ];

        foreach ($users as $user) {
            // Décision locale depuis la carte préchargée (zéro SELECT supplémentaire par user).
            $prefRow = $prefMap->get($user->id);
            $enabled = $prefRow !== null ? (bool) $prefRow->enabled : $defaultEnabled;

            if (! $enabled) {
                continue;
            }

            if ($this->send(
                self::TYPE_ANNOUNCEMENT,
                $user,
                'Nouvelle annonce : ' . $announcement->title,
                'academy::emails.announcement',
                $data,
            )) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Réponse à un sujet de forum -> à l'AUTEUR du sujet (jamais l'auteur de la réponse).
     * Scope : l'auteur doit toujours participer au cours (inscrit actif ou gérant).
     */
    public function forumReply(ForumTopic $topic, ForumPost $post, Course $course): bool
    {
        if (! $this->isMasterEnabled()) {
            return false;
        }

        $authorId = $topic->user_id;
        if ($authorId === null || (int) $authorId === (int) $post->user_id) {
            return false;
        }

        $author = User::find($authorId);
        if ($author === null || ! $this->participatesIn($author, $course)) {
            return false;
        }

        // Dedup : un post précis ne notifie qu'une seule fois l'auteur (anti-retry).
        $dedupKey = 'forum_reply:post-' . $post->id;

        return $this->send(
            self::TYPE_FORUM_REPLY,
            $author,
            'Nouvelle réponse à votre sujet : ' . $topic->title,
            'academy::emails.forum-reply',
            [
                'course'   => $course,
                'topic'    => $topic,
                'post'     => $post,
                'bodyHtml' => $post->renderedBody(),
                'ctaUrl'   => $this->courseUrl($course),
                'ctaLabel' => 'Voir la discussion',
            ],
            $dedupKey,
        );
    }

    /**
     * Travail corrigé (devoir ou essai) -> à l'étudiant concerné.
     *
     * @param  int|null    $scorePercent  Note finale en pourcentage (facultatif, affichée si fournie).
     * @param  string|null $dedupKey      Clé de dédoublonnage basée sur l'entité source
     *                                   (ex. « graded:submission-{id} », « graded:attempt-{id} »).
     *                                   Si null, aucun dédoublonnage n'est appliqué.
     */
    public function graded(User $student, Course $course, string $itemTitle, ?int $scorePercent = null, ?string $dedupKey = null): bool
    {
        if (! $this->isMasterEnabled()) {
            return false;
        }

        return $this->send(
            self::TYPE_GRADED,
            $student,
            'Votre travail a été corrigé : ' . $itemTitle,
            'academy::emails.graded',
            [
                'course'       => $course,
                'itemTitle'    => $itemTitle,
                'scorePercent' => $scorePercent,
                'ctaUrl'       => $this->courseUrl($course),
                'ctaLabel'     => 'Voir ma note',
            ],
            $dedupKey,
        );
    }

    /**
     * Cours complété (certificat émis) -> à l'étudiant.
     */
    public function courseCompleted(User $student, Course $course, CertificateIssued $certificate): bool
    {
        if (! $this->isMasterEnabled()) {
            return false;
        }

        $certUrl  = Route::has('academy.certificates.show')
            ? route('academy.certificates.show', $certificate->public_url_slug)
            : $this->courseUrl($course);

        // Dedup : un certificat est unique par (user, course) -> une seule notification.
        $dedupKey = 'course_completed:cert-' . $certificate->id;

        return $this->send(
            self::TYPE_COURSE_COMPLETED,
            $student,
            'Félicitations : vous avez complété « ' . $course->title . ' »',
            'academy::emails.course-completed',
            [
                'course'      => $course,
                'certificate' => $certificate,
                'ctaUrl'      => $certUrl,
                'ctaLabel'    => 'Voir mon certificat',
            ],
            $dedupKey,
        );
    }

    /**
     * Rappel d'échéance à venir -> à l'utilisateur inscrit.
     * IDEMPOTENT : la clé de dédoublonnage (type + identifiant + jour de l'échéance)
     * empêche tout second rappel de la même échéance au même utilisateur.
     *
     * @param  array<string, mixed>  $event  Échéance normalisée (CalendarService).
     */
    public function dueReminder(User $user, array $event): bool
    {
        if (! $this->isMasterEnabled()) {
            return false;
        }

        $startsAt = $event['starts_at'] ?? null;
        $dayKey   = is_object($startsAt) && method_exists($startsAt, 'format') ? $startsAt->format('Ymd') : 'na';
        $dedupKey = 'due:' . ($event['id'] ?? 'na') . ':' . $dayKey;

        return $this->send(
            self::TYPE_DUE_REMINDER,
            $user,
            'Rappel d\'échéance : ' . ($event['title'] ?? 'À venir'),
            'academy::emails.due-reminder',
            [
                'event'    => $event,
                'ctaUrl'   => isset($event['course_slug']) && Route::has('academy.courses.calendar')
                    ? route('academy.courses.calendar', $event['course_slug'])
                    : url('/academie/espace'),
                'ctaLabel' => 'Voir le calendrier',
            ],
            $dedupKey,
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // POINT D'ENVOI UNIQUE (toutes les gardes vivent ICI)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * SEUL point d'envoi. Applique l'interrupteur maître, la préférence et le
     * dédoublonnage, puis délègue l'envoi transactionnel à BrevoService.
     * Retourne true uniquement si un courriel a réellement été transmis.
     *
     * @param  array<string, mixed>  $data  Variables passées au gabarit Blade.
     */
    private function send(string $type, User $user, string $subject, string $view, array $data, ?string $dedupKey = null): bool
    {
        // GARDE 1 - INTERRUPTEUR MAITRE. Aucun envoi tant qu'il est faux ; on
        // journalise pour traçabilité (préparée mais non envoyée).
        if (! $this->isMasterEnabled()) {
            Log::info('[Academy] Notification préparée mais NON envoyée (interrupteur maître désactivé).', [
                'type'    => $type,
                'user_id' => $user->id,
            ]);

            return false;
        }

        if (empty($user->email)) {
            return false;
        }

        // GARDE 2 - PRÉFÉRENCE utilisateur (opt-out respecté).
        if (! $this->isEnabledFor($user, $type)) {
            return false;
        }

        // GARDE 3 - DÉDOUBLONNAGE (rappels) : ne jamais renvoyer la même notification.
        if ($dedupKey !== null && $this->alreadySent($user, $type, $dedupKey)) {
            return false;
        }

        try {
            $html = View::make($view, array_merge($data, [
                'subject'        => $subject,
                'recipientName'  => $user->name,
                'preferencesUrl' => $this->preferencesUrl(),
            ]))->render();

            $result = $this->brevo->sendCampaignEmail($user->email, (string) $user->name, $subject, $html);

            $success = (bool) ($result['success'] ?? false);

            if ($success && $dedupKey !== null) {
                $this->recordSent($user, $type, $dedupKey);
            }

            if (! $success) {
                Log::warning('[Academy] Échec d\'envoi de notification.', [
                    'type'    => $type,
                    'user_id' => $user->id,
                    'error'   => $result['error'] ?? null,
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            Log::error('[Academy] Exception lors de l\'envoi de notification : ' . $e->getMessage(), [
                'type'    => $type,
                'user_id' => $user->id,
            ]);

            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // OUTILS INTERNES
    // ─────────────────────────────────────────────────────────────────────────────

    /** Inscrits ACTIFS d'un cours (scope strict anti-fuite). @return Collection<int, User> */
    private function activeStudents(Course $course): Collection
    {
        $userIds = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        return User::whereIn('id', $userIds)->get();
    }

    /** Vrai si l'utilisateur participe au cours : inscrit actif OU membre de l'équipe. */
    private function participatesIn(User $user, Course $course): bool
    {
        $isActive = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if ($isActive) {
            return true;
        }

        return CourseRole::query()
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    private function alreadySent(User $user, string $type, string $dedupKey): bool
    {
        return NotificationLog::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->where('dedup_key', $dedupKey)
            ->exists();
    }

    private function recordSent(User $user, string $type, string $dedupKey): void
    {
        NotificationLog::firstOrCreate(
            ['user_id' => $user->id, 'type' => $type, 'dedup_key' => $dedupKey],
            ['sent_at' => now()],
        );
    }

    private function courseUrl(Course $course): string
    {
        return Route::has('academy.courses.show')
            ? route('academy.courses.show', $course->slug)
            : url('/academie');
    }

    private function preferencesUrl(): string
    {
        return Route::has('academy.notifications.preferences')
            ? route('academy.notifications.preferences')
            : url('/academie/espace');
    }
}
