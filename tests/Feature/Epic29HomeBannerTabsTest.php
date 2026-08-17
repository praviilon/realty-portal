<?php

namespace Tests\Feature;

use App\Livewire\Home\Index as HomePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Эпик 29 (Веха 3) — доработка главной страницы: баннер с вкладками
 * (жильё/коммерция/рабочие пространства) и поиск по типам, каждая вкладка
 * ведёт в свой каталог со своим набором фильтров.
 */
class Epic29HomeBannerTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_active_tab_is_residential(): void
    {
        Livewire::test(HomePage::class)
            ->assertSet('activeCategory', 'residential');
    }

    public function test_switching_tabs_changes_active_category(): void
    {
        Livewire::test(HomePage::class)
            ->call('switchCategory', 'commercial')
            ->assertSet('activeCategory', 'commercial')
            ->call('switchCategory', 'workspace')
            ->assertSet('activeCategory', 'workspace');
    }

    public function test_invalid_category_is_ignored(): void
    {
        Livewire::test(HomePage::class)
            ->call('switchCategory', 'bogus')
            ->assertSet('activeCategory', 'residential');
    }

    public function test_residential_tab_search_redirects_to_residential_catalog(): void
    {
        Livewire::test(HomePage::class)
            ->set('searchDealType', 'rent')
            ->set('searchPropertyType', 'house')
            ->call('search')
            ->assertRedirect(route('residential.search', ['dealType' => 'rent', 'propertyType' => 'house']));
    }

    public function test_commercial_tab_search_redirects_to_commercial_catalog(): void
    {
        Livewire::test(HomePage::class)
            ->call('switchCategory', 'commercial')
            ->set('searchCommercialDealType', 'rent')
            ->set('searchPurposeType', 'office')
            ->call('search')
            ->assertRedirect(route('commercial.search', ['dealType' => 'rent', 'purposeType' => 'office']));
    }

    public function test_workspace_tab_search_redirects_to_workspace_catalog(): void
    {
        Livewire::test(HomePage::class)
            ->call('switchCategory', 'workspace')
            ->set('searchWorkspaceType', 'office')
            ->call('search')
            ->assertRedirect(route('workspace.search', ['workspaceType' => 'office']));
    }

    public function test_home_page_shows_all_three_tab_buttons(): void
    {
        $this->get('/')
            ->assertSee('Жильё')
            ->assertSee('Коммерция')
            ->assertSee('Рабочие пространства');
    }
}
