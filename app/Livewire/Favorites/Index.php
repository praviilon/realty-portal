<?php

namespace App\Livewire\Favorites;

use App\Models\CommercialProperty;
use App\Models\Favorite;
use App\Models\ResidentialProperty;
use App\Models\Workspace;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Страница «Избранное» — эпик 17 дорожной карты (Веха 2). Вкладки по типам
 * объявлений (жильё/коммерция); с эпика 28 (Веха 3) сюда же добавлена вкладка
 * «Рабочие пространства» — благодаря полиморфной связи favoritable
 * потребовалось лишь добавить ещё один пункт в TABS без изменения схемы.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    protected const TABS = [
        'residential' => ResidentialProperty::class,
        'commercial' => CommercialProperty::class,
        'workspace' => Workspace::class,
    ];

    public string $tab = 'residential';

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, self::TABS)) {
            $this->tab = $tab;
        }
    }

    public function removeFavorite(int $favoriteId): void
    {
        Favorite::query()
            ->where('user_id', auth()->id())
            ->where('id', $favoriteId)
            ->delete();
    }

    public function render()
    {
        $type = self::TABS[$this->tab] ?? ResidentialProperty::class;

        $favorites = Favorite::query()
            ->where('user_id', auth()->id())
            ->where('favoritable_type', $type)
            ->with('favoritable')
            ->latest('added_at')
            ->get()
            ->filter(fn (Favorite $favorite) => $favorite->favoritable !== null);

        $counts = collect(self::TABS)->map(
            fn (string $class) => Favorite::query()
                ->where('user_id', auth()->id())
                ->where('favoritable_type', $class)
                ->count()
        );

        return view('livewire.favorites.index', [
            'favorites' => $favorites,
            'counts' => $counts,
        ]);
    }
}
