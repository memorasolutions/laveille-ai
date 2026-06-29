<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Backoffice\Livewire\Concerns\WithInfiniteScroll;

class NotificationsTable extends Component
{
    use WithInfiniteScroll, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    /**
     * Supprime une notification de l'utilisateur connecté.
     * Requiert la permission manage_notifications (cohérent avec la route DELETE).
     */
    public function deleteNotification(string $id): void
    {
        abort_if(! auth()->user()?->can('manage_notifications'), 403);
        auth()->user()?->notifications()->where('id', $id)->delete();
        $this->dispatch('toast', type: 'success', message: __('Notification supprimée.'));
    }

    public function render(): View
    {
        $notifications = auth()->user()?->notifications()->paginate($this->perPage);

        return view('backoffice::livewire.notifications-table', compact('notifications'));
    }
}
