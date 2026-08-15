<?php

namespace App\Livewire\Notifications;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Колокольчик уведомлений в шапке — эпик 11 дорожной карты. Использует
 * встроенные database-notifications Laravel (см. раздел 1 плана).
 */
class Bell extends Component
{
    public function markAsRead(string $notificationId)
    {
        $notification = Auth::user()->notifications()->whereKey($notificationId)->first();

        if (! $notification) {
            return null;
        }

        $notification->markAsRead();

        $data = $notification->data;

        return match ($notification->type) {
            \App\Notifications\ListingStatusChanged::class => $this->redirect(route('dashboard'), navigate: true),
            \App\Notifications\NewChatMessage::class => $this->redirect(route('chat.show', $data['chat_id']), navigate: true),
            default => null,
        };
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->limit(10)
            ->get();

        return view('livewire.notifications.bell', [
            'notifications' => $notifications,
            'unreadCount' => Auth::user()->unreadNotifications()->count(),
        ]);
    }
}
