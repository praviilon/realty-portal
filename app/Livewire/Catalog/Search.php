<?php

namespace App\Livewire\Catalog;

use App\Models\ResidentialProperty;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Search extends Component
{
    use WithPagination;

    #[Url]
    public string $dealType = 'sale'; // sale | rent

    #[Url]
    public string $propertyType = ''; // '' | apartment | house | room | studio

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
     * пространствами (см. App\Livewire\Catalog\WorkspaceSearch). У жилой
     * недвижимости пока единственная особенность — "Нет лифта", см.
     * ResidentialProperty::floorFeatureLabels().
     *
     * @var array<int, string>
     */
    #[Url]
    public array $floorFeatures = [];

    #[Url]
    public string $view = 'list'; // list | map

    /**
     * Выделенная на карте область — эпик 19 дорожной карты (Веха 2).
     * Массив вершин полигона [['lat' => ..., 'lng' => ...], ...], минимум 3.
     */
    #[Url]
    public array $areaPolygon = [];

    public function updated($property): void
    {
        if (in_array($property, ['dealType', 'propertyType', 'priceMin', 'priceMax', 'areaMin', 'areaMax']) || str_starts_with((string) $property, 'floorFeatures')) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['propertyType', 'priceMin', 'priceMax', 'areaMin', 'areaMax', 'floorFeatures']);
        $this->resetPage();
    }

    /**
     * Вызывается из resources/js/yandex-map.js после того, как пользователь
     * выделил на карте прямоугольную область (клик по двум противоположным углам).
     */
    public function applyAreaSelection(array $points): void
    {
        $this->areaPolygon = $points;
        $this->resetPage();
    }

    public function clearAreaSelection(): void
    {
        $this->areaPolygon = [];
        $this->resetPage();
    }

    protected function filteredQuery(): Builder
    {
        $query = ResidentialProperty::query()
            ->active()
            ->where('deal_type', $this->dealType)
            ->when($this->propertyType, fn ($q) => $q->where('property_type', $this->propertyType))
            ->when($this->priceMin, fn ($q) => $q->where('price', '>=', $this->priceMin))
            ->when($this->priceMax, fn ($q) => $q->where('price', '<=', $this->priceMax))
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
     * MySQL (прод/дев) — быстрая фильтрация через ST_Contains и SPATIAL INDEX
     * на сгенерированной колонке location (см. миграцию
     * 2024_02_04_000001_add_location_point_to_residential_properties_table).
     * На любой другой СУБД (sqlite в тестах, см. phpunit.xml) — тот же результат
     * средствами PHP (алгоритм ray casting по lat/lng), без ST_Contains.
     *
     * Полигон намеренно интерпретируется как плоский (без привязки к SRID
     * 4326/эллипсоиду) — см. комментарий в миграции.
     */
    protected function applyAreaFilter(Builder $query): Builder
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            $ring = collect($this->areaPolygon)
                ->push($this->areaPolygon[0])
                ->map(fn (array $point) => $point['lng'].' '.$point['lat'])
                ->implode(', ');

            return $query->whereRaw('ST_Contains(ST_GeomFromText(?), location)', ["POLYGON(({$ring}))"]);
        }

        $polygon = $this->areaPolygon;

        $matchingIds = (clone $query)
            ->get(['id', 'lat', 'lng'])
            ->filter(fn (ResidentialProperty $listing) => self::pointInPolygon(
                (float) $listing->lat,
                (float) $listing->lng,
                $polygon
            ))
            ->pluck('id');

        return $query->whereIn('id', $matchingIds);
    }

    /**
     * Простой ray casting — является ли точка (lat, lng) внутри полигона,
     * заданного массивом вершин [['lat' => ..., 'lng' => ...], ...].
     */
    protected static function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $inside = false;
        $count = count($polygon);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $latI = (float) $polygon[$i]['lat'];
            $lngI = (float) $polygon[$i]['lng'];
            $latJ = (float) $polygon[$j]['lat'];
            $lngJ = (float) $polygon[$j]['lng'];

            $intersects = (($latI > $lat) !== ($latJ > $lat))
                && ($lng < ($lngJ - $lngI) * ($lat - $latI) / ($latJ - $latI) + $lngI);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Пины для карты — эпик "Карта объектов (пины из результатов поиска)".
     * Без пагинации, но с разумным лимитом, чтобы не перегружать карту.
     */
    protected function pins(): array
    {
        return $this->filteredQuery()
            ->latest()
            ->limit(200)
            ->get(['id', 'lat', 'lng', 'price', 'address'])
            ->map(fn (ResidentialProperty $listing) => [
                'id' => $listing->id,
                'lat' => (float) $listing->lat,
                'lng' => (float) $listing->lng,
                'price' => $listing->price,
                'address' => $listing->address,
                'url' => route('residential.show', $listing),
            ])
            ->all();
    }

    public function render()
    {
        $listings = $this->filteredQuery()->with('mainPhoto')->latest()->paginate(12);
        $pins = $this->pins();

        $this->dispatch('catalog:pins-updated', pins: $pins);

        return view('livewire.catalog.search', [
            'listings' => $listings,
            'pins' => $pins,
            'propertyTypeLabels' => ResidentialProperty::propertyTypeLabels(),
            'floorFeatureLabels' => ResidentialProperty::floorFeatureLabels(),
        ]);
    }
}
