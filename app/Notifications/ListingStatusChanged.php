<?php

namespace App\Notifications;

use App\Models\ResidentialProperty;
use Illuminate\Notifications\Notification;

/**
 * Уведомление об одобрении/отклонении объявления модератором — эпик 11
 * дорожной карты (встроенные database-notifications Laravel, без своей
 * миграции, см. раздел 1 плана).
 */
class ListingStatusChanged extends Notification
{
    public function __construct(protected ResidentialProperty $listing)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'listing_id' => $this->listing->id,
            'listing_type' => ResidentialProperty::class,
            'status' => $this->listing->status,
            'address' => $this->listing->address,
            'rejection_reason' => $this->listing->rejection_reason,
            'message' => $this->listing->status === 'active'
                ? "Объявление «{$this->listing->address}» одобрено и опубликовано."
                : "Объявление «{$this->listing->address}» отклонено модератором.",
        ];
    }
}
