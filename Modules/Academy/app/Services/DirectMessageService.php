<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Messagerie directe (DM) formateur ↔ apprenant — SOURCE UNIQUE (DRY) de la
 * logique d'envoi. Chaque envoi revérifie l'autorisation en direct (relation
 * pédagogique commune), applique un rate limit anti-spam, valide/nettoie le
 * contenu (texte brut, longueur plafonnée) et notifie le destinataire via
 * AcademyNotificationService (réutilisé tel quel, pas de 2e système).
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Academy\Models\DirectMessage;
use Modules\Academy\Models\DirectMessageConversation;

final class DirectMessageService
{
    /** Rate limit anti-spam : au plus N messages par minute et par expéditeur. */
    public const RATE_LIMIT_MAX = 20;

    public const RATE_LIMIT_DECAY_SECONDS = 60;

    public function __construct(private readonly AcademyNotificationService $notifications) {}

    /** Vrai si l'utilisateur peut ENCORE envoyer un message maintenant (avant de tenter). */
    public function tooManyAttempts(User $sender): bool
    {
        return RateLimiter::tooManyAttempts($this->rateLimitKey($sender), self::RATE_LIMIT_MAX);
    }

    /**
     * Liste des contacts autorisés pour l'utilisateur courant : formateurs de
     * SES cours (s'il est apprenant) ET apprenants de SES cours (s'il est
     * formateur). Toujours dérivé des tables pédagogiques réelles, jamais d'une
     * liste libre — c'est la même règle que canMessage(), appliquée en masse.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function allowedContactsFor(User $user): \Illuminate\Support\Collection
    {
        $instructorCourseIds = \Modules\Academy\Models\CourseRole::query()
            ->where('user_id', $user->id)
            ->pluck('course_id');

        $enrolledCourseIds = \Modules\Academy\Models\Enrollment::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('course_id');

        // Apprenants des cours où JE suis formateur.
        $studentIds = \Modules\Academy\Models\Enrollment::query()
            ->whereIn('course_id', $instructorCourseIds)
            ->where('status', 'active')
            ->where('user_id', '!=', $user->id)
            ->pluck('user_id');

        // Formateurs des cours où JE suis apprenant.
        $instructorIds = \Modules\Academy\Models\CourseRole::query()
            ->whereIn('course_id', $enrolledCourseIds)
            ->where('user_id', '!=', $user->id)
            ->pluck('user_id');

        $ids = $studentIds->merge($instructorIds)->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return User::query()->whereIn('id', $ids)->orderBy('name')->get();
    }

    /**
     * Ouvre (ou crée) le fil entre l'utilisateur courant et un contact autorisé.
     * Lève RuntimeException si la relation pédagogique n'existe pas (traduit en
     * abort_if(403) par l'appelant Livewire — jamais de fil créé sans relation).
     */
    public function openConversation(User $current, User $other): DirectMessageConversation
    {
        return DirectMessageConversation::findOrCreateFor($current, $other);
    }

    /**
     * Envoie un message dans une conversation. Re-vérifie l'autorisation ET le
     * rate limit ICI (défense en profondeur, indépendante de la Policy Livewire).
     * Retourne le message créé, ou null si bloqué (rate limit / autorisation).
     */
    public function send(DirectMessageConversation $conversation, User $sender, string $body): ?DirectMessage
    {
        if (! $conversation->hasParticipant($sender)) {
            return null;
        }

        $recipient = $conversation->otherParticipant($sender);
        if ($recipient === null || ! DirectMessageConversation::canMessage($sender, $recipient)) {
            return null;
        }

        if ($this->tooManyAttempts($sender)) {
            return null;
        }

        $body = trim(strip_tags($body));
        if ($body === '' || mb_strlen($body) > DirectMessage::MAX_LENGTH) {
            return null;
        }

        RateLimiter::hit($this->rateLimitKey($sender), self::RATE_LIMIT_DECAY_SECONDS);

        $message = DirectMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $sender->id,
            'recipient_id'    => $recipient->id,
            'body'            => $body,
        ]);

        $conversation->forceFill(['last_message_at' => $message->created_at])->save();

        $this->notifyRecipient($recipient, $sender, $conversation, $message);

        return $message;
    }

    /** Marque tous les messages d'un fil comme lus pour l'utilisateur donné. */
    public function markConversationRead(DirectMessageConversation $conversation, User $reader): int
    {
        if (! $conversation->hasParticipant($reader)) {
            return 0;
        }

        return DirectMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('recipient_id', $reader->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /** Nombre total de messages non lus adressés à l'utilisateur (toutes conversations). */
    public function unreadCountFor(User $user): int
    {
        return DirectMessage::query()
            ->where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    private function notifyRecipient(User $recipient, User $sender, DirectMessageConversation $conversation, DirectMessage $message): void
    {
        // Réutilise le point d'envoi unique existant (interrupteur maître +
        // préférence + Brevo) plutôt que de créer un 2e système de courriel.
        $this->notifications->directMessageReceived($recipient, $sender, $conversation, $message);
    }

    private function rateLimitKey(User $sender): string
    {
        return 'academy_dm:' . $sender->id;
    }
}
