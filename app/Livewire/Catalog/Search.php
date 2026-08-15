<?php

namespace App\Livewire\Catalog;

use App\Models\ResidentialProperty;
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

    public function render()
    {
        $listings = ResidentialProperty::query()
            ->active()
            ->where('deal_type', $this->dealType)
            ->when($this->propertyType, fn ($q) => $q->where('property_type', $this->propertyType))
            ->when($this->priceMin, fn ($q) => $q->where('price', '>=', $this->priceMin))
            ->when($this->priceMax, fn ($q) => $q->where('price', '<=', $this->priceMax))
            ->latest()
            ->paginate(12);

        return view('livewire.catalog.search', [
            'listings' => $listings,
            'propertyTypeLabels' => ResidentialProperty::propertyTypeLabels(),
        ]);
    }
}
