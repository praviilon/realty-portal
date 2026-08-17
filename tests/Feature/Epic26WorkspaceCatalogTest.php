<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspacePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Epic26WorkspaceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_shows_map_and_listing_cards_side_by_side(): void
    {
        $listing = Workspace::factory()->create(['status' => 'active', 'address' => 'ул. Коворкинг, 3']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 3000]);

        $response = $this->get(route('workspace.search'));

        // Фиксированная колонка карты — не переключатель список/карта (как у жилой и
        // коммерческой), карта и список видны одновременно на одной странице (без
        // API-ключа рендерится заглушка компонента x-yandex-map, но сам компонент
        // всегда присутствует на странице).
        $response->assertOk()
            ->assertSee($listing->address)
            ->assertSee('sticky top-6', false);
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
