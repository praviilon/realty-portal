<?php

namespace Tests\Feature;

use App\Models\CommercialProperty;
use App\Models\CommercialSaleDetail;
use App\Models\PropertyPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Epic16CommercialCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_displays_full_listing_details(): void
    {
        $seller = User::factory()->create(['first_name' => 'Пётр', 'last_name' => 'Иванов']);
        $listing = CommercialProperty::factory()->create([
            'status' => 'active',
            'user_id' => $seller->id,
            'address' => 'ул. Деловая, 10',
            'area' => 120,
            'deal_type' => 'sale',
        ]);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id, 'price' => 25000000]);

        $buyer = User::factory()->create();

        $response = $this->actingAs($buyer)->get(route('commercial.show', $listing));

        $response->assertStatus(200)
            ->assertSee('ул. Деловая, 10')
            ->assertSee('25 000 000')
            ->assertSee('120')
            ->assertSee('Пётр Иванов')
            ->assertSee('Написать');
    }

    public function test_rent_listing_shows_price_per_month_and_deposit(): void
    {
        $listing = CommercialProperty::factory()->create(['status' => 'active', 'deal_type' => 'rent']);
        \App\Models\CommercialRentDetail::factory()->create([
            'property_id' => $listing->id,
            'price_per_month' => 150000,
            'deposit' => 150000,
            'utilities_included' => true,
        ]);

        $this->get(route('commercial.show', $listing))
            ->assertSee('150 000')
            ->assertSee('Включены');
    }

    public function test_show_page_displays_photos_when_present(): void
    {
        $listing = CommercialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);
        PropertyPhoto::factory()->create([
            'photoable_id' => $listing->id,
            'photoable_type' => CommercialProperty::class,
            'path' => 'property-photos/commercial-test-photo.webp',
        ]);

        $this->get(route('commercial.show', $listing))
            ->assertSee('property-photos/commercial-test-photo.webp', false);
    }

    public function test_views_count_increments_once_per_session(): void
    {
        $listing = CommercialProperty::factory()->create(['status' => 'active', 'views_count' => 0, 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        $this->get(route('commercial.show', $listing));
        $this->get(route('commercial.show', $listing));

        $this->assertSame(1, $listing->fresh()->views_count);
    }

    public function test_moderation_listing_hidden_from_strangers_but_visible_to_owner(): void
    {
        $owner = User::factory()->create();
        $listing = CommercialProperty::factory()->moderation()->create(['user_id' => $owner->id, 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('commercial.show', $listing))->assertNotFound();
        $this->actingAs($owner)->get(route('commercial.show', $listing))->assertOk();
    }

    public function test_map_is_rendered_on_show_page(): void
    {
        $listing = CommercialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        $this->get(route('commercial.show', $listing))
            ->assertSee('Расположение на карте');
    }

    public function test_navigation_links_to_commercial_catalog(): void
    {
        $this->get(route('home'))->assertSee(route('commercial.search'), false);
    }

    public function test_catalog_to_show_page_navigation_flow(): void
    {
        $listing = CommercialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id, 'price' => 8000000]);

        $this->get(route('commercial.search'))
            ->assertOk()
            ->assertSee($listing->address)
            ->assertSee(route('commercial.show', $listing), false);
    }
}
