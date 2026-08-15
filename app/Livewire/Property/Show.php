<?php

namespace App\Livewire\Property;

use App\Models\ResidentialProperty;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Детальная карточка объявления (жилая недвижимость) — эпик 6 дорожной карты.
 *
 * Кнопка "Написать продавцу" — заглушка: сама переписка появится в эпике 10
 * (Чаты между пользователями), пока рано на неё ссылаться.
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

    public function render()
    {
        $pin = [[
            'id' => $this->listing->id,
            'lat' => (float) $this->listing->lat,
            'lng' => (float) $this->listing->lng,
            'price' => $this->listing->price,
            'address' => $this->listing->address,
        ]];

        return view('livewire.property.show', ['pin' => $pin]);
    }
}
