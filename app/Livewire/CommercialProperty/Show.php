<?php

namespace App\Livewire\CommercialProperty;

use App\Models\Chat;
use App\Models\CommercialProperty;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Детальная карточка объявления (коммерческая недвижимость) — эпик 16
 * дорожной карты (Веха 2). По аналогии с App\Livewire\Property\Show для
 * жилой недвижимости (эпик 6), но с дополнительными полями коммерческого
 * объекта и веткой цены аренда/продажа.
 */
#[Layout('layouts.app')]
class Show extends Component
{
    public CommercialProperty $listing;

    public function mount(CommercialProperty $commercialProperty): void
    {
        $this->listing = $commercialProperty->load([
            'user',
            'saleDetail',
            'rentDetail',
            'photos' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        // Публично видны только активные объявления — модерация/отклонённые/архив скрыты
        // по прямой ссылке даже от автора объявления (см. аналогичную логику
        // для жилой; ранее владелец мог просматривать своё неактивное
        // объявление напрямую — это было сообщено как баг и намеренно убрано).
        abort_unless($this->listing->status === 'active', 404);

        $this->registerView();
    }

    protected function registerView(): void
    {
        $sessionKey = "viewed_commercial_property_{$this->listing->id}";

        if (session()->has($sessionKey)) {
            return;
        }

        session()->put($sessionKey, true);
        $this->listing->increment('views_count');
    }

    public function startChat()
    {
        $chat = Chat::findOrCreateFor(Auth::user(), $this->listing);

        return $this->redirect(route('chat.show', $chat), navigate: true);
    }

    public function render()
    {
        $pin = [[
            'id' => $this->listing->id,
            'lat' => (float) $this->listing->lat,
            'lng' => (float) $this->listing->lng,
            'price' => $this->listing->display_price,
            'address' => $this->listing->address,
        ]];

        return view('livewire.commercial-property.show', [
            'pin' => $pin,
            'floorFeatureLabels' => CommercialProperty::floorFeatureLabels(),
            'heatingTypeLabels' => CommercialProperty::heatingTypeLabels(),
            'finishingTypeLabels' => CommercialProperty::finishingTypeLabels(),
            'furnitureLabels' => CommercialProperty::furnitureLabels(),
            'entranceTypeLabels' => CommercialProperty::entranceTypeLabels(),
            'rentTypeLabels' => CommercialProperty::rentTypeLabels(),
        ]);
    }
}
