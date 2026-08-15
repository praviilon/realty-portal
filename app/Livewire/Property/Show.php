<?php

namespace App\Livewire\Property;

use App\Models\ResidentialProperty;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Детальная карточка объявления (жилая недвижимость).
 *
 * NB: это базовая версия для эпика "Каталог + поиск/фильтры" (нужна, чтобы
 * ссылки из каталога вели куда-то реальное). Полноценная доработка —
 * фотогалерея, кнопка "Написать продавцу", карта — эпик 6 дорожной карты.
 */
#[Layout('layouts.app')]
class Show extends Component
{
    public ResidentialProperty $listing;

    public function mount(ResidentialProperty $residentialProperty): void
    {
        $this->listing = $residentialProperty;

        // Публично видны только активные объявления — модерация/отклонённые/архив скрыты
        // от посторонних, кроме автора объявления.
        abort_unless(
            $this->listing->status === 'active' || auth()->id() === $this->listing->user_id,
            404
        );
    }

    public function render()
    {
        return view('livewire.property.show');
    }
}
