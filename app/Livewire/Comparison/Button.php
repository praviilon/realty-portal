<?php

namespace App\Livewire\Comparison;

use App\Models\ComparisonItem;
use App\Models\ComparisonList;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

/**
 * Кнопка «В сравнение» — эпик 18 дорожной карты (Веха 2). По аналогии с
 * App\Livewire\Favorites\Button, но список сравнения зависит от типа
 * объявления (App\Models\ComparisonList::listTypeLabels) и ограничен
 * 3 объектами — оба правила проверяются здесь, а не в БД (см. миграции).
 */
class Button extends Component
{
    public const LIMIT = 3;

    public Model $comparable;

    public bool $isAdded = false;

    public ?string $limitMessage = null;

    public function mount(Model $comparable): void
    {
        $this->comparable = $comparable;
        $this->isAdded = $this->existingItemQuery()->exists();
    }

    public function toggle()
    {
        if (! auth()->check()) {
            return $this->redirect(route('login'), navigate: true);
        }

        $this->limitMessage = null;

        $existing = $this->existingItemQuery()->first();

        if ($existing) {
            $existing->delete();
            $this->isAdded = false;

            return;
        }

        $list = ComparisonList::query()->firstOrCreate([
            'user_id' => auth()->id(),
            'list_type' => $this->comparable->comparisonListType(),
        ]);

        if ($list->items()->count() >= self::LIMIT) {
            $this->limitMessage = 'Можно сравнивать не более '.self::LIMIT.' объектов одновременно. Уберите один из списка сравнения, чтобы добавить другой.';

            return;
        }

        $list->items()->create([
            'comparable_type' => $this->comparable::class,
            'comparable_id' => $this->comparable->id,
            'added_at' => now(),
        ]);
        $this->isAdded = true;
    }

    protected function existingItemQuery()
    {
        return ComparisonItem::query()
            ->where('comparable_type', $this->comparable::class)
            ->where('comparable_id', $this->comparable->id)
            ->whereHas('comparisonList', fn ($q) => $q->where('user_id', auth()->id()));
    }

    public function render()
    {
        return view('livewire.comparison.button');
    }
}
