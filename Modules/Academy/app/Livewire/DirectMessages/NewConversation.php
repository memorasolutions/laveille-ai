<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Messagerie directe (DM) — démarrer un NOUVEAU fil. La liste de contacts
 * proposée est TOUJOURS dérivée des tables pédagogiques réelles
 * (DirectMessageService::allowedContactsFor) : jamais de champ libre « id
 * utilisateur », jamais de recherche globale d'utilisateurs (anti-harcèlement).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\DirectMessages;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Services\DirectMessageService;

class NewConversation extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::check(), 403);
        abort_unless((bool) config('academy.direct_messaging_enabled', false), 404);
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function contacts(): Collection
    {
        return app(DirectMessageService::class)->allowedContactsFor(Auth::user());
    }

    /**
     * Ouvre (ou retrouve) le fil avec un contact autorisé et redirige vers lui.
     * Re-vérifie que le contact figure bien dans la liste autorisée (anti-IDOR :
     * un id arbitraire posté depuis le client ne suffit jamais).
     */
    public function startConversation(int $userId): void
    {
        $user = Auth::user();

        $other = $this->contacts->firstWhere('id', $userId);
        abort_if($other === null, 403, "Ce contact n'est pas autorisé.");

        $conversation = app(DirectMessageService::class)->openConversation($user, $other);

        $this->redirect(route('academy.messages.show', $conversation->id), navigate: true);
    }

    public function render()
    {
        return view('academy::livewire.direct-messages.new-conversation');
    }
}
