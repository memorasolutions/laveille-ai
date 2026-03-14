<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Illuminate\Support\Collection;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;

    public Collection $notifications;

    public function mount(): void
    {
        $this->notifications = collect();
        $this->unreadCount = auth()->user()->unreadNotifications()->count();
    }

    /** @return array<string, string> */
    public function getListeners(): array
    {
        $userId = auth()->id();

        return [
            "echo-private:App.Models.User.{$userId},notification.received" => 'onNotificationReceived',
        ];
    }

    public function onNotificationReceived(array $payload): void
    {
        $this->refresh();

        $this->dispatch('notification-toast', [
            'type' => $payload['type'] ?? 'info',
            'title' => __('Nouvelle notification'),
            'message' => $payload['message'] ?? '',
        ]);
    }

    public function loadNotifications(): Collection
    {
        return auth()->user()->unreadNotifications()->latest()->take(5)->get();
    }

    public function markRead(string $id): void
    {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
        }
        $this->refresh();
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        $this->refresh();
    }

    private function refresh(): void
    {
        $this->unreadCount = auth()->user()->unreadNotifications()->count();
        $this->notifications = $this->loadNotifications();
    }

    public function render(): \Illuminate\View\View
    {
        return view('backoffice::livewire.notification-bell', [
            'notifications' => $this->loadNotifications(),
        ]);
    }
}
