<?php

namespace Tests\Feature;

use App\Models\ResidentialProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Epic3CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_shows_only_active_listings(): void
    {
        ResidentialProperty::factory()->create(['deal_type' => 'sale', 'price' => 5000000, 'status' => 'active']);
        ResidentialProperty::factory()->moderation()->create(['deal_type' => 'sale', 'price' => 5000000]);
        ResidentialProperty::factory()->rejected()->create(['deal_type' => 'sale', 'price' => 5000000]);

        Livewire::test(\App\Livewire\Catalog\Search::class)
            ->assertViewHas('listings', fn ($listings) => $listings->total() === 1);
    }

    public function test_catalog_filters_by_deal_type(): void
    {
        ResidentialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active']);
        ResidentialProperty::factory()->create(['deal_type' => 'rent', 'status' => 'active']);

        Livewire::test(\App\Livewire\Catalog\Search::class)
            ->set('dealType', 'rent')
            ->assertViewHas('listings', fn ($listings) => $listings->total() === 1);
    }

    public function test_catalog_filters_by_property_type(): void
    {
        ResidentialProperty::factory()->create(['deal_type' => 'sale', 'property_type' => 'house', 'status' => 'active']);
        ResidentialProperty::factory()->create(['deal_type' => 'sale', 'property_type' => 'apartment', 'status' => 'active']);

        Livewire::test(\App\Livewire\Catalog\Search::class)
            ->set('propertyType', 'house')
            ->assertViewHas('listings', fn ($listings) => $listings->total() === 1);
    }

    public function test_catalog_filters_by_price_range(): void
    {
        ResidentialProperty::factory()->create(['deal_type' => 'sale', 'price' => 1000000, 'status' => 'active']);
        ResidentialProperty::factory()->create(['deal_type' => 'sale', 'price' => 9000000, 'status' => 'active']);

        Livewire::test(\App\Livewire\Catalog\Search::class)
            ->set('priceMin', 5000000)
            ->assertViewHas('listings', fn ($listings) => $listings->total() === 1);
    }

    public function test_reset_filters_clears_state(): void
    {
        Livewire::test(\App\Livewire\Catalog\Search::class)
            ->set('propertyType', 'house')
            ->set('priceMin', 100)
            ->set('priceMax', 200)
            ->call('resetFilters')
            ->assertSet('propertyType', '')
            ->assertSet('priceMin', null)
            ->assertSet('priceMax', null);
    }

    public function test_show_page_displays_active_listing(): void
    {
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'address' => 'ул. Тестовая, 1']);

        $this->get(route('residential.show', $listing))
            ->assertStatus(200)
            ->assertSee('ул. Тестовая, 1');
    }

    public function test_show_page_404s_for_moderation_listing_to_guest(): void
    {
        $listing = ResidentialProperty::factory()->moderation()->create();

        $this->get(route('residential.show', $listing))->assertStatus(404);
    }

    public function test_show_page_visible_to_owner_even_if_not_active(): void
    {
        $owner = User::factory()->create();
        $listing = ResidentialProperty::factory()->moderation()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('residential.show', $listing))
            ->assertStatus(200);
    }
}
