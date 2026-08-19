<?php

namespace App\Livewire\Catalog;

use App\Livewire\Catalog\Concerns\HasAreaSelection;
use App\Models\CommercialProperty;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Каталог коммерческой недвижимости — эпики 15 (фильтры) и 16 (каталог/карточка),
 * Веха 2. Отдельный компонент от жилого каталога (App\Livewire\Catalog\Search),
 * т.к. набор фильтров другой (purpose_type вместо property_type) и цена лежит не
 * в самой таблице, а в связанной commercial_rent_details/commercial_sale_details
 * в зависимости от dealType — см. раздел 3 технического плана.
 */
#[Layout('layouts.app')]
class CommercialSearch extends Component
{
    use HasAreaSelection;
    use WithPagination;

    #[Url]
    public string $dealType = 'sale'; // sale | rent

    #[Url]
    public string $purposeType = ''; // '' | office | retail | warehouse | free

    #[Url]
    public string $buildingType = ''; // '' | administrative | business_center | residential | shopping_center

    #[Url]
    public ?int $priceMin = null;

    #[Url]
    public ?int $priceMax = null;

    #[Url]
    public ?int $areaMin = null;

    #[Url]
    public ?int $areaMax = null;

    /**
     * Чекбоксы по особенностям помещения — по аналогии с рабочими
     * пространствами (см. App\Livewire\Catalog\WorkspaceSearch), см.
     * CommercialProperty::floorFeatureLabels().
     *
     * @var array<int, string>
     */
    #[Url]
    public array $floorFeatures = [];

    #[Url]
    public string $view = 'list'; // list | map

    public function updated($property): void
    {
        if (in_array($property, ['dealType', 'purposeType', 'buildingType', 'priceMin', 'priceMax', 'areaMin', 'areaMax']) || str_starts_with((string) $property, 'floorFeatures')) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['purposeType', 'buildingType', 'priceMin', 'priceMax', 'areaMin', 'areaMax', 'floorFeatures']);
        $this->resetPage();
    }

    protected function priceRelationAndColumn(): array
    {
        return $this->dealType === 'rent'
            ? ['rentDetail', 'price_per_month']
            : ['saleDetail', 'price'];
    }

    protected function filteredQuery(): Builder
    {
        [$relation, $column] = $this->priceRelationAndColumn();

        $query = CommercialProperty::query()
            ->active()
            ->with(['saleDetail', 'rentDetail'])
            ->where('deal_type', $this->dealType)
            ->when($this->purposeType, fn ($q) => $q->where('purpose_type', $this->purposeType))
            ->when($this->buildingType, fn ($q) => $q->where('building_type', $this->buildingType))
            ->when($this->priceMin || $this->priceMax, fn ($q) => $q->whereHas(
                $relation,
                fn ($detail) => $detail
                    ->when($this->priceMin, fn ($d) => $d->where($column, '>=', $this->priceMin))
                    ->when($this->priceMax, fn ($d) => $d->where($column, '<=', $this->priceMax))
            ))
            ->when($this->areaMin, fn ($q) => $q->where('area', '>=', $this->areaMin))
            ->when($this->areaMax, fn ($q) => $q->where('area', '<=', $this->areaMax))
            ->when(! empty($this->floorFeatures), function ($q) {
                foreach ($this->floorFeatures as $feature) {
                    $q->whereJsonContains('floor_features', $feature);
                }
            });

        if (count($this->areaPolygon) >= 3) {
            $query = $this->applyAreaFilter($query);
        }

        return $query;
    }

    /**
     * Пины для карты — по аналогии с жилым каталогом (эпик 5).
     */
    protected function pins(): array
    {
        return $this->filteredQuery()
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (CommercialProperty $listing) => [
                'id' => $listing->id,
                'lat' => (float) $listing->lat,
                'lng' => (float) $listing->lng,
                'price' => $listing->display_price,
                'address' => $listing->address,
                'url' => route('commercial.show', $listing),
            ])
            ->all();
    }

    public function render()
    {
        $listings = $this->filteredQuery()->with('mainPhoto')->latest()->paginate(12);
        $pins = $this->pins();

        $this->dispatch('catalog:pins-updated', pins: $pins);

        return view('livewire.catalog.commercial-search', [
            'listings' => $listings,
            'pins' => $pins,
            'purposeTypeLabels' => CommercialProperty::purposeTypeLabels(),
            'buildingTypeLabels' => CommercialProperty::buildingTypeLabels(),
            'floorFeatureLabels' => CommercialProperty::floorFeatureLabels(),
        ]);
    }
}
