<?php

namespace App\Livewire\Home;

use App\Models\Faq;
use App\Models\ResidentialProperty;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $searchDealType = 'sale';

    public string $searchPropertyType = '';

    public function search(): void
    {
        $this->redirect(route('residential.search', array_filter([
            'dealType' => $this->searchDealType,
            'propertyType' => $this->searchPropertyType,
        ])));
    }

    public function render()
    {
        $featured = ResidentialProperty::query()
            ->active()
            ->latest()
            ->take(6)
            ->get();

        $faqsByCategory = Faq::query()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return view('livewire.home.index', [
            'featured' => $featured,
            'faqsByCategory' => $faqsByCategory,
            'propertyTypeLabels' => ResidentialProperty::propertyTypeLabels(),
        ]);
    }
}
