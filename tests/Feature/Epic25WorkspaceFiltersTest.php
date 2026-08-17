<?php

namespace Tests\Feature;

use App\Livewire\Catalog\WorkspaceSearch;
use App\Models\Workspace;
use App\Models\WorkspacePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Epic25WorkspaceFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_listings_are_shown(): void
    {
        $active = Workspace::factory()->create(['status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $active->id, 'period' => 'day', 'price' => 2000]);

        $moderation = Workspace::factory()->moderation()->create();
        WorkspacePricing::factory()->create(['workspace_id' => $moderation->id, 'period' => 'day', 'price' => 2000]);

        Livewire::test(WorkspaceSearch::class)
            ->assertSee($active->address)
            ->assertDontSee($moderation->address);
    }

    public function test_filters_by_workspace_type(): void
    {
        $office = Workspace::factory()->create(['status' => 'active', 'workspace_type' => 'office']);
        WorkspacePricing::factory()->create(['workspace_id' => $office->id, 'period' => 'day', 'price' => 2000]);

        $meetingRoom = Workspace::factory()->create(['status' => 'active', 'workspace_type' => 'meeting_room']);
        WorkspacePricing::factory()->create(['workspace_id' => $meetingRoom->id, 'period' => 'day', 'price' => 2000]);

        Livewire::test(WorkspaceSearch::class)
            ->set('workspaceType', 'office')
            ->assertSee($office->address)
            ->assertDontSee($meetingRoom->address);
    }

    public function test_filters_price_range_for_selected_period(): void
    {
        $cheap = Workspace::factory()->create(['status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $cheap->id, 'period' => 'day', 'price' => 1000]);

        $expensive = Workspace::factory()->create(['status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $expensive->id, 'period' => 'day', 'price' => 9000]);

        Livewire::test(WorkspaceSearch::class)
            ->set('period', 'day')
            ->set('priceMin', 5000)
            ->assertSee($expensive->address)
            ->assertDontSee($cheap->address);
    }

    public function test_price_filter_only_matches_the_selected_period(): void
    {
        $listing = Workspace::factory()->create(['status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'hour', 'price' => 9000]);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'month', 'price' => 500]);

        // Дорогая почасовая цена не должна попадать под фильтр по помесячной цене.
        Livewire::test(WorkspaceSearch::class)
            ->set('period', 'month')
            ->set('priceMin', 5000)
            ->assertDontSee($listing->address);
    }

    public function test_filters_by_area_range(): void
    {
        $small = Workspace::factory()->create(['status' => 'active', 'area' => 10]);
        WorkspacePricing::factory()->create(['workspace_id' => $small->id, 'period' => 'day', 'price' => 2000]);

        $large = Workspace::factory()->create(['status' => 'active', 'area' => 100]);
        WorkspacePricing::factory()->create(['workspace_id' => $large->id, 'period' => 'day', 'price' => 2000]);

        Livewire::test(WorkspaceSearch::class)
            ->set('areaMin', 50)
            ->assertSee($large->address)
            ->assertDontSee($small->address);
    }

    public function test_filters_by_amenity(): void
    {
        $withWifi = Workspace::factory()->create(['status' => 'active', 'amenities' => ['wifi', 'coffee']]);
        WorkspacePricing::factory()->create(['workspace_id' => $withWifi->id, 'period' => 'day', 'price' => 2000]);

        $withoutWifi = Workspace::factory()->create(['status' => 'active', 'amenities' => ['printer']]);
        WorkspacePricing::factory()->create(['workspace_id' => $withoutWifi->id, 'period' => 'day', 'price' => 2000]);

        Livewire::test(WorkspaceSearch::class)
            ->set('amenities', ['wifi'])
            ->assertSee($withWifi->address)
            ->assertDontSee($withoutWifi->address);
    }

    public function test_reset_filters_clears_all_criteria(): void
    {
        Livewire::test(WorkspaceSearch::class)
            ->set('workspaceType', 'office')
            ->set('priceMin', 1000)
            ->set('priceMax', 5000)
            ->set('areaMin', 10)
            ->set('areaMax', 50)
            ->set('amenities', ['wifi'])
            ->call('resetFilters')
            ->assertSet('workspaceType', '')
            ->assertSet('priceMin', null)
            ->assertSet('priceMax', null)
            ->assertSet('areaMin', null)
            ->assertSet('areaMax', null)
            ->assertSet('amenities', []);
    }

    public function test_catalog_page_loads(): void
    {
        $this->get(route('workspace.search'))->assertOk();
    }
}
