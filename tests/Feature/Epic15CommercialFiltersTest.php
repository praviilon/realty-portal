<?php

namespace Tests\Feature;

use App\Livewire\Catalog\CommercialSearch;
use App\Models\CommercialProperty;
use App\Models\CommercialRentDetail;
use App\Models\CommercialSaleDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Epic15CommercialFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_listings_of_selected_deal_type_are_shown(): void
    {
        $sale = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active']);
        CommercialSaleDetail::factory()->create(['property_id' => $sale->id, 'price' => 5000000]);

        $rent = CommercialProperty::factory()->create(['deal_type' => 'rent', 'status' => 'active']);
        CommercialRentDetail::factory()->create(['property_id' => $rent->id, 'price_per_month' => 100000]);

        $moderation = CommercialProperty::factory()->moderation()->create(['deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $moderation->id]);

        Livewire::test(CommercialSearch::class)
            ->set('dealType', 'sale')
            ->assertSee($sale->address)
            ->assertDontSee($rent->address)
            ->assertDontSee($moderation->address);
    }

    public function test_filters_by_purpose_type(): void
    {
        $office = CommercialProperty::factory()->create(['deal_type' => 'sale', 'purpose_type' => 'office', 'status' => 'active']);
        CommercialSaleDetail::factory()->create(['property_id' => $office->id]);

        $warehouse = CommercialProperty::factory()->create(['deal_type' => 'sale', 'purpose_type' => 'warehouse', 'status' => 'active']);
        CommercialSaleDetail::factory()->create(['property_id' => $warehouse->id]);

        Livewire::test(CommercialSearch::class)
            ->set('dealType', 'sale')
            ->set('purposeType', 'office')
            ->assertSee($office->address)
            ->assertDontSee($warehouse->address);
    }

    public function test_filters_sale_price_range_via_related_table(): void
    {
        $cheap = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active']);
        CommercialSaleDetail::factory()->create(['property_id' => $cheap->id, 'price' => 3000000]);

        $expensive = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active']);
        CommercialSaleDetail::factory()->create(['property_id' => $expensive->id, 'price' => 20000000]);

        Livewire::test(CommercialSearch::class)
            ->set('dealType', 'sale')
            ->set('priceMin', 10000000)
            ->assertSee($expensive->address)
            ->assertDontSee($cheap->address);
    }

    public function test_filters_rent_price_range_via_related_table(): void
    {
        $cheap = CommercialProperty::factory()->create(['deal_type' => 'rent', 'status' => 'active']);
        CommercialRentDetail::factory()->create(['property_id' => $cheap->id, 'price_per_month' => 50000]);

        $expensive = CommercialProperty::factory()->create(['deal_type' => 'rent', 'status' => 'active']);
        CommercialRentDetail::factory()->create(['property_id' => $expensive->id, 'price_per_month' => 500000]);

        Livewire::test(CommercialSearch::class)
            ->set('dealType', 'rent')
            ->set('priceMax', 100000)
            ->assertSee($cheap->address)
            ->assertDontSee($expensive->address);
    }

    /**
     * Доработка по просьбе пользователя: фильтр по типу здания.
     */
    public function test_filters_by_building_type(): void
    {
        $businessCenter = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'building_type' => 'business_center']);
        CommercialSaleDetail::factory()->create(['property_id' => $businessCenter->id]);

        $shoppingCenter = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'building_type' => 'shopping_center']);
        CommercialSaleDetail::factory()->create(['property_id' => $shoppingCenter->id]);

        Livewire::test(CommercialSearch::class)
            ->set('dealType', 'sale')
            ->set('buildingType', 'business_center')
            ->assertSee($businessCenter->address)
            ->assertDontSee($shoppingCenter->address);
    }

    /**
     * Доработка по просьбе пользователя: фильтр по площади ("Площадь от/до")
     * — по аналогии с рабочими пространствами.
     */
    public function test_filters_by_area_range(): void
    {
        $small = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'area' => 50]);
        CommercialSaleDetail::factory()->create(['property_id' => $small->id]);

        $large = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'area' => 500]);
        CommercialSaleDetail::factory()->create(['property_id' => $large->id]);

        Livewire::test(CommercialSearch::class)
            ->set('dealType', 'sale')
            ->set('areaMin', 200)
            ->assertSee($large->address)
            ->assertDontSee($small->address);
    }

    /**
     * Доработка по просьбе пользователя: чекбоксы по особенностям
     * помещения (Охрана/видеонаблюдение, Парковка и т.д.) — по аналогии с
     * рабочими пространствами.
     */
    public function test_filters_by_floor_feature(): void
    {
        $withParking = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'floor_features' => ['parking']]);
        CommercialSaleDetail::factory()->create(['property_id' => $withParking->id]);

        $withoutParking = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'floor_features' => ['security']]);
        CommercialSaleDetail::factory()->create(['property_id' => $withoutParking->id]);

        Livewire::test(CommercialSearch::class)
            ->set('dealType', 'sale')
            ->set('floorFeatures', ['parking'])
            ->assertSee($withParking->address)
            ->assertDontSee($withoutParking->address);
    }

    public function test_reset_filters_clears_purpose_and_price(): void
    {
        Livewire::test(CommercialSearch::class)
            ->set('purposeType', 'office')
            ->set('buildingType', 'business_center')
            ->set('priceMin', 1000000)
            ->set('priceMax', 5000000)
            ->set('areaMin', 10)
            ->set('areaMax', 500)
            ->set('floorFeatures', ['parking'])
            ->call('resetFilters')
            ->assertSet('purposeType', '')
            ->assertSet('buildingType', '')
            ->assertSet('priceMin', null)
            ->assertSet('priceMax', null)
            ->assertSet('areaMin', null)
            ->assertSet('areaMax', null)
            ->assertSet('floorFeatures', []);
    }

    public function test_catalog_page_loads(): void
    {
        $this->get(route('commercial.search'))->assertOk();
    }
}
