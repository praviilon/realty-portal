<?php

namespace App\Livewire\Comparison;

use App\Models\ComparisonItem;
use App\Models\ComparisonList;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Страница «Сравнение» — эпик 18 дорожной карты (Веха 2). Отдельная вкладка
 * на каждое сочетание «тип объекта + вид сделки» (residential_sale,
 * residential_rent, commercial_sale, commercial_rent), т.к. сравнивать
 * между собой можно только однородные объявления.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    public const TABS = [
        'residential_sale' => 'Жильё — продажа',
        'residential_rent' => 'Жильё — аренда',
        'commercial_sale' => 'Коммерция — продажа',
        'commercial_rent' => 'Коммерция — аренда',
    ];

    public string $tab = 'residential_sale';

    public function mount(): void
    {
        // Открываем сразу непустую вкладку, если она есть — иначе пользователь
        // попадает на "Жильё — продажа" даже когда сравнивает только коммерцию.
        $firstNonEmpty = ComparisonList::query()
            ->where('user_id', auth()->id())
            ->whereHas('items')
            ->orderBy('id')
            ->value('list_type');

        if ($firstNonEmpty) {
            $this->tab = $firstNonEmpty;
        }
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, self::TABS)) {
            $this->tab = $tab;
        }
    }

    public function removeItem(int $itemId): void
    {
        ComparisonItem::query()
            ->where('id', $itemId)
            ->whereHas('comparisonList', fn ($q) => $q->where('user_id', auth()->id()))
            ->delete();
    }

    public function clearList(): void
    {
        $list = ComparisonList::query()
            ->where('user_id', auth()->id())
            ->where('list_type', $this->tab)
            ->first();

        $list?->items()->delete();
    }

    public function render()
    {
        $list = ComparisonList::query()
            ->where('user_id', auth()->id())
            ->where('list_type', $this->tab)
            ->with(['items.comparable'])
            ->first();

        $items = $list
            ? $list->items->filter(fn (ComparisonItem $item) => $item->comparable !== null)->values()
            : collect();

        $isResidential = str_starts_with($this->tab, 'residential');

        $counts = collect(array_keys(self::TABS))->mapWithKeys(
            fn (string $type) => [$type => ComparisonList::query()
                ->where('user_id', auth()->id())
                ->where('list_type', $type)
                ->withCount('items')
                ->first()
                ?->items_count ?? 0]
        );

        return view('livewire.comparison.index', [
            'items' => $items,
            'isResidential' => $isResidential,
            'counts' => $counts,
        ]);
    }
}
