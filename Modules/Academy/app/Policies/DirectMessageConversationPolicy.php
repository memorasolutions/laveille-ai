<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Autorisation serveur de la messagerie directe (DM). Modèle « participant
 * uniquement » : contrairement à CoursePolicy (rôle + ownership PAR COURS),
 * ici il n'y a AUCUN accès admin de contournement — un fil privé n'est visible
 * que par ses DEUX participants, un point c'est tout (vie privée Loi 25 QC).
 */

declare(strict_types=1);

namespace Modules\Academy\Policies;

use App\Models\User;
use Modules\Academy\Models\DirectMessageConversation;

class DirectMessageConversationPolicy
{
    /** Lecture du fil : uniquement l'un des deux participants (anti-IDOR). */
    public function view(User $user, DirectMessageConversation $conversation): bool
    {
        return $conversation->hasParticipant($user);
    }

    /** Envoi d'un message dans le fil : identique à view() + relation pédagogique toujours valide. */
    public function send(User $user, DirectMessageConversation $conversation): bool
    {
        if (! $conversation->hasParticipant($user)) {
            return false;
        }

        $other = $conversation->otherParticipant($user);

        return $other !== null && DirectMessageConversation::canMessage($user, $other);
    }
}
