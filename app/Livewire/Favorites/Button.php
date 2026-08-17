<?php

namespace App\Livewire\Favorites;

use App\Models\Favorite;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

/**
 * Кнопка «В избранное» — эпик 17 дорожной карты (Веха 2). Переиспользуется
 * на карточках каталога (жильё/коммерция) и на детальных карточках.
 * Работает с любой моделью через полиморфную связь favoritable, поэтому
 * без изменений подойдёт и для рабочих пространств в Вехе 3.
 */
class Button extends Component
{
    public Model $favoritable;

    public bool $isFavorited = false;

    public function mount(Model $favoritable): void
    {
        $this->favoritable = $favoritable;
        $this->isFavorited = $this->existingFavoriteQuery()->exists();
    }

    public function toggle()
    {
        if (! auth()->check()) {
            return $this->redirect(route('login'), navigate: true);
        }

        $favorite = $this->existingFavoriteQuery()->first();

        if ($favorite) {
            $favorite->delete();
            $this->isFavorited = false;

            return;
        }

        Favorite::create([
            'user_id' => auth()->id(),
            'favoritable_type' => $this->favoritable::class,
            'favoritable_id' => $this->favoritable->id,
            'added_at' => now(),
        ]);
        $this->isFavorited = true;
    }

    protected function existingFavoriteQuery()
    {
        return Favorite::query()
            ->where('user_id', auth()->id())
            ->where('favoritable_type', $this->favoritable::class)
            ->where('favoritable_id', $this->favoritable->id);
    }

    public function render()
    {
        return view('livewire.favorites.button');
    }
}
