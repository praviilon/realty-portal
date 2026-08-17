<?php

namespace App\Livewire\Home;

use App\Models\CommercialProperty;
use App\Models\Faq;
use App\Models\ResidentialProperty;
use App\Models\Workspace;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Главная страница — эпик 4 дорожной карты (Веха 1). С эпика 29 (Веха 3)
 * баннер с формой поиска стал вкладочным (жильё/коммерция/рабочие
 * пространства) — каждая вкладка ведёт на свой каталог со своим набором
 * фильтров, т.к. у типов объявлений разные критерии поиска.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    public string $activeCategory = 'residential'; // residential | commercial | workspace

    // Жильё
    public string $searchDealType = 'sale';

    public string $searchPropertyType = '';

    // Коммерция
    public string $searchCommercialDealType = 'sale';

    public string $searchPurposeType = '';

    // Рабочие пространства
    public string $searchWorkspaceType = '';

    public function switchCategory(string $category): void
    {
        if (in_array($category, ['residential', 'commercial', 'workspace'], true)) {
            $this->activeCategory = $category;
        }
    }

    public function search(): void
    {
        $this->redirect(match ($this->activeCategory) {
            'commercial' => route('commercial.search', array_filter([
                'dealType' => $this->searchCommercialDealType,
                'purposeType' => $this->searchPurposeType,
            ])),
            'workspace' => route('workspace.search', array_filter([
                'workspaceType' => $this->searchWorkspaceType,
            ])),
            default => route('residential.search', array_filter([
                'dealType' => $this->searchDealType,
                'propertyType' => $this->searchPropertyType,
            ])),
        });
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
            'purposeTypeLabels' => CommercialProperty::purposeTypeLabels(),
            'workspaceTypeLabels' => Workspace::workspaceTypeLabels(),
        ]);
    }
}
