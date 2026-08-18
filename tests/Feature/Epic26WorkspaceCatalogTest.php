<?php

namespace Tests\Feature;

use App\Livewire\Catalog\WorkspaceSearch;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspacePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class Epic26WorkspaceCatalogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ОБНОВЛЕНО (унификация каталогов по просьбе пользователя): раньше карта
     * была фиксированной колонкой сбоку (класс "sticky top-6") без
     * переключателя. Теперь — как в жилой/коммерческой недвижимости: список
     * показывается по умолчанию, переключатель "Список"/"Карта" управляет
     * свойством $view, карта рендерится только при view === 'map'.
     */
    public function test_catalog_shows_listing_cards_and_view_toggle(): void
    {
        $listing = Workspace::factory()->create(['status' => 'active', 'address' => 'ул. Коворкинг, 3']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 3000]);

        $response = $this->get(route('workspace.search'));

        $response->assertOk()
            ->assertSee($listing->address)
            ->assertSee('Список')
            ->assertSee('Карта')
            ->assertDontSee('sticky top-6', false);
    }

    public function test_catalog_map_view_renders_selectable_map_with_area_selection_ui(): void
    {
        Config::set('services.yandex_maps.api_key', 'test-fake-key');

        Livewire::test(WorkspaceSearch::class)
            ->set('view', 'map')
            ->assertSee('Выделить область на карте', false);
    }

    public function test_listing_card_links_to_show_page(): void
    {
        $listing = Workspace::factory()->create(['status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 3000]);

        $this->get(route('workspace.search'))
            ->assertOk()
            ->assertSee(route('workspace.show', $listing), false);
    }

    public function test_listing_card_shows_cheapest_price(): void
    {
        $listing = Workspace::factory()->create(['status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'hour', 'price' => 500]);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'month', 'price' => 25000]);

        $this->get(route('workspace.search'))
            ->assertOk()
            ->assertSee('500');
    }

    public function test_navigation_links_to_workspace_catalog(): void
    {
        $this->get(route('home'))->assertSee(route('workspace.search'), false);
    }

    public function test_moderation_listing_hidden_from_catalog(): void
    {
        $listing = Workspace::factory()->moderation()->create(['address' => 'ул. Скрытая, 9']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 3000]);

        $this->get(route('workspace.search'))
            ->assertOk()
            ->assertDontSee('ул. Скрытая, 9');
    }
}
