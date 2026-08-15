<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Уведомление о новом сообщении в чате — эпик 11 дорожной карты.
 */
class NewChatMessage extends Notification
{
    public function __construct(protected Message $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'chat_id' => $this->message->chat_id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender->full_name,
            'preview' => Str::limit($this->message->text, 60),
            'message' => "{$this->message->sender->full_name}: " . Str::limit($this->message->text, 60),
        ];
    }
}
