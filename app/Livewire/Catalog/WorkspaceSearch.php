<?php

namespace App\Livewire\Catalog;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Поиск/фильтры + каталог рабочих пространств — эпики 25 и 26 дорожной карты
 * (Веха 3), по образцу App\Livewire\Catalog\CommercialSearch (эпики 15-16,
 * Веха 2). Отличия от коммерческой недвижимости: цена лежит не в одной
 * связанной таблице 1:1, а в workspace_pricing — 1:M по периодам
 * (час/сутки/неделя/месяц), поэтому фильтр по цене ищет по выбранному периоду
 * через whereHas('pricing', ...). Карта на этой странице — не переключаемая
 * вкладка, а фиксированная колонка рядом со списком (см. раздел 4 плана,
 * "Каталог/карта (фиксированная колонка)").
 */
#[Layout('layouts.app')]
class WorkspaceSearch extends Component
{
    use WithPagination;

    #[Url]
    public string $workspaceType = ''; // '' | workspace | office | meeting_room | conference_room

    #[Url]
    public string $period = 'day'; // hour | day | week | month — период, по которому фильтруется цена

    #[Url]
    public ?int $priceMin = null;

    #[Url]
    public ?int $priceMax = null;

    #[Url]
    public ?int $areaMin = null;

    #[Url]
    public ?int $areaMax = null;

    /** @var array<int, string> */
    #[Url]
    public array $amenities = [];

    public function updated($property): void
    {
        if (in_array($property, ['workspaceType', 'period', 'priceMin', 'priceMax', 'areaMin', 'areaMax']) || str_starts_with((string) $property, 'amenities')) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['workspaceType', 'priceMin', 'priceMax', 'areaMin', 'areaMax', 'amenities']);
        $this->period = 'day';
        $this->resetPage();
    }

    protected function filteredQuery(): Builder
    {
        return Workspace::query()
            ->active()
            ->with('pricing')
            ->when($this->workspaceType, fn ($q) => $q->where('workspace_type', $this->workspaceType))
            ->when($this->priceMin || $this->priceMax, fn ($q) => $q->whereHas(
                'pricing',
                fn ($p) => $p->where('period', $this->period)
                    ->when($this->priceMin, fn ($d) => $d->where('price', '>=', $this->priceMin))
                    ->when($this->priceMax, fn ($d) => $d->where('price', '<=', $this->priceMax))
            ))
            ->when($this->areaMin, fn ($q) => $q->where('area', '>=', $this->areaMin))
            ->when($this->areaMax, fn ($q) => $q->where('area', '<=', $this->areaMax))
            ->when(! empty($this->amenities), function ($q) {
                foreach ($this->amenities as $amenity) {
                    $q->whereJsonContains('amenities', $amenity);
                }
            });
    }

    /**
     * Пины для фиксированной колонки карты — эпик 26.
     */
    protected function pins(): array
    {
        return $this->filteredQuery()
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (Workspace $listing) => [
                'id' => $listing->id,
                'lat' => (float) $listing->lat,
                'lng' => (float) $listing->lng,
                'price' => $listing->display_price,
                'address' => $listing->address,
                'url' => route('workspace.show', $listing),
            ])
            ->all();
    }

    public function render()
    {
        $listings = $this->filteredQuery()->with('mainPhoto')->latest()->paginate(12);
        $pins = $this->pins();

        $this->dispatch('catalog:pins-updated', pins: $pins);

        return view('livewire.catalog.workspace-search', [
            'listings' => $listings,
            'pins' => $pins,
            'workspaceTypeLabels' => Workspace::workspaceTypeLabels(),
            'amenityLabels' => Workspace::amenityLabels(),
            'periodLabels' => Workspace::pricingPeriodLabels(),
        ]);
    }
}
