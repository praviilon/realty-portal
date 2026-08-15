<?php

namespace App\Livewire\Chat;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Переписка по одному диалогу — эпик 10 дорожной карты.
 * Обновление входящих сообщений через wire:poll (3–5 сек), см. раздел 1 плана:
 * на обычном хостинге без WebSocket-сервера это единственный рабочий вариант.
 */
#[Layout('layouts.app')]
class Thread extends Component
{
    public Chat $chat;

    public string $text = '';

    public function mount(Chat $chat): void
    {
        abort_unless($chat->isParticipant(Auth::id()), 403);

        $this->chat = $chat;

        $this->markIncomingAsRead();
    }

    protected function markIncomingAsRead(): void
    {
        $this->chat->messages()
            ->where('sender_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function send(): void
    {
        $this->validate([
            'text' => ['required', 'string', 'max:2000'],
        ]);

        Message::create([
            'chat_id' => $this->chat->id,
            'sender_id' => Auth::id(),
            'text' => $this->text,
        ]);

        $this->reset('text');
    }

    /**
     * Вызывается при каждом wire:poll — подтягивает новые сообщения и
     * отмечает их прочитанными, пока диалог открыт.
     */
    public function poll(): void
    {
        $this->markIncomingAsRead();
    }

    public function render()
    {
        $messages = $this->chat->messages()->with('sender')->oldest()->get();

        return view('livewire.chat.thread', ['messages' => $messages]);
    }
}
