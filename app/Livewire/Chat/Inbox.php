<?php

namespace App\Livewire\Chat;

use App\Models\Chat;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Список диалогов пользователя — эпик 10 дорожной карты (Livewire + polling).
 */
#[Layout('layouts.app')]
class Inbox extends Component
{
    public function render()
    {
        $userId = Auth::id();

        $chats = Chat::query()
            ->where('buyer_id', $userId)
            ->orWhere('seller_id', $userId)
            ->with(['buyer', 'seller', 'listable', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('is_read', false)->where('sender_id', '!=', $userId)])
            ->get()
            ->sortByDesc(fn (Chat $chat) => optional($chat->messages->first())->created_at ?? $chat->created_at)
            ->values();

        return view('livewire.chat.inbox', ['chats' => $chats]);
    }
}
