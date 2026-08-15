<?php

namespace App\Livewire\Catalog;

use App\Models\ResidentialProperty;
use Illuminate\Database\Eloquent\Builder;
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
    public string $view = 'list'; // list | map

    public function updated($property): void
    {
        if (in_array($property, ['dealType', 'propertyType', 'priceMin', 'priceMax'])) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['propertyType', 'priceMin', 'priceMax']);
        $this->resetPage();
    }

    protected function filteredQuery(): Builder
    {
        return ResidentialProperty::query()
            ->active()
            ->where('deal_type', $this->dealType)
            ->when($this->propertyType, fn ($q) => $q->where('property_type', $this->propertyType))
            ->when($this->priceMin, fn ($q) => $q->where('price', '>=', $this->priceMin))
            ->when($this->priceMax, fn ($q) => $q->where('price', '<=', $this->priceMax));
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
        $listings = $this->filteredQuery()->latest()->paginate(12);
        $pins = $this->pins();

        $this->dispatch('catalog:pins-updated', pins: $pins);

        return view('livewire.catalog.search', [
            'listings' => $listings,
            'pins' => $pins,
            'propertyTypeLabels' => ResidentialProperty::propertyTypeLabels(),
        ]);
    }
}
