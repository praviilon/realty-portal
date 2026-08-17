<?php

namespace App\Notifications;

use App\Models\CommercialProperty;
use App\Models\ResidentialProperty;
use App\Models\Workspace;
use Illuminate\Notifications\Notification;

/**
 * Уведомление об одобрении/отклонении объявления модератором — эпик 11
 * дорожной карты (встроенные database-notifications Laravel, без своей
 * миграции, см. раздел 1 плана). С эпика 13 (Веха 2) переиспользуется для
 * коммерческой недвижимости, а с эпика 24 (Веха 3) — и для рабочих
 * пространств: у всех трёх моделей одинаковый набор нужных полей
 * (address/status/rejection_reason), поэтому один класс уведомления на все типы.
 */
class ListingStatusChanged extends Notification
{
    public function __construct(protected ResidentialProperty|CommercialProperty|Workspace $listing)
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
            'listing_type' => $this->listing::class,
            'status' => $this->listing->status,
            'address' => $this->listing->address,
            'rejection_reason' => $this->listing->rejection_reason,
            'message' => $this->listing->status === 'active'
                ? "Объявление «{$this->listing->address}» одобрено и опубликовано."
                : "Объявление «{$this->listing->address}» отклонено модератором.",
        ];
    }
}
