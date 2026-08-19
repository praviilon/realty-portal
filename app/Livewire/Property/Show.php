<?php

namespace App\Livewire\Property;

use App\Models\Chat;
use App\Models\ResidentialProperty;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Детальная карточка объявления (жилая недвижимость) — эпик 6 дорожной карты.
 * Кнопка "Написать продавцу" ведёт в чат — эпик 10 дорожной карты.
 */
#[Layout('layouts.app')]
class Show extends Component
{
    public ResidentialProperty $listing;

    public function mount(ResidentialProperty $residentialProperty): void
    {
        $this->listing = $residentialProperty->load(['user', 'photos' => fn ($q) => $q->orderBy('sort_order')]);

        // Публично видны только активные объявления — модерация/отклонённые/архив скрыты
        // от посторонних, кроме автора объявления.
        abort_unless(
            $this->listing->status === 'active' || auth()->id() === $this->listing->user_id,
            404
        );

        $this->registerView();
    }

    /**
     * Увеличиваем views_count не чаще одного раза за сессию на объявление —
     * чтобы обновление страницы не накручивало счётчик бесконечно.
     */
    protected function registerView(): void
    {
        $sessionKey = "viewed_residential_property_{$this->listing->id}";

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
            'price' => $this->listing->price,
            'address' => $this->listing->address,
        ]];

        return view('livewire.property.show', [
            'pin' => $pin,
            'heatingTypeLabels' => ResidentialProperty::heatingTypeLabels(),
            'finishingTypeLabels' => ResidentialProperty::finishingTypeLabels(),
            'furnitureLabels' => ResidentialProperty::furnitureLabels(),
            'floorFeatureLabels' => ResidentialProperty::floorFeatureLabels(),
        ]);
    }
}
