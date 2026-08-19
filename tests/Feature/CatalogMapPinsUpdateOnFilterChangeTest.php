<?php

namespace Tests\Feature;

use App\Livewire\Catalog\CommercialSearch;
use App\Livewire\Catalog\Search;
use App\Livewire\Catalog\WorkspaceSearch;
use App\Models\CommercialProperty;
use App\Models\CommercialRentDetail;
use App\Models\CommercialSaleDetail;
use App\Models\ResidentialProperty;
use App\Models\Workspace;
use App\Models\WorkspacePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Доработка по просьбе пользователя: на каталогах недвижимости при смене
 * фильтров (тип сделки, цена и т.д.) карточки списка обновляются сразу, а
 * пины на карте (вкладка "Карта") раньше могли оставаться от предыдущего
 * набора результатов. Карта слушает браузерное событие
 * `catalog:pins-updated`, которое каждый из трёх Livewire-компонентов
 * каталога диспатчит на каждом render() — см. resources/js/yandex-map.js.
 *
 * Эти тесты закрепляют СЕРВЕРНУЮ часть контракта: событие должно
 * диспатчиться с АКТУАЛЬНЫМ (отфильтрованным) набором пинов после КАЖДОГО
 * изменения фильтра, для всех трёх каталогов — жилой, коммерческой
 * недвижимости и рабочих пространств. Клиентская часть (сам приём события
 * и обновление меток на карте в Alpine-компоненте yandexMap) дополнительно
 * проверена вручную через Playwright против реально запущенного
 * приложения (с подменой Yandex Maps API на стаб, так как в тестовой
 * среде нет доступа к реальному API/ключу).
 */
class CatalogMapPinsUpdateOnFilterChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.yandex_maps.api_key', 'test-fake-key');
    }

    // --- Жилая недвижимость ---

    public function test_residential_pins_updated_event_reflects_deal_type_filter_change(): void
    {
        $sale = ResidentialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active']);
        $rent = ResidentialProperty::factory()->create(['deal_type' => 'rent', 'status' => 'active']);

        Livewire::test(Search::class)
            ->set('view', 'map')
            ->assertDispatched('catalog:pins-updated', function (string $name, array $params) use ($sale) {
                return count($params['pins']) === 1 && $params['pins'][0]['id'] === $sale->id;
            })
            ->set('dealType', 'rent')
            ->assertDispatched('catalog:pins-updated', function (string $name, array $params) use ($rent) {
                return count($params['pins']) === 1 && $params['pins'][0]['id'] === $rent->id;
            });
    }

    public function test_residential_pins_updated_event_reflects_price_filter_change(): void
    {
        $cheap = ResidentialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'price' => 3_000_000]);
        $expensive = ResidentialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'price' => 15_000_000]);

        Livewire::test(Search::class)
            ->set('view', 'map')
            ->set('priceMin', 10_000_000)
            ->assertDispatched('catalog:pins-updated', function (string $name, array $params) use ($expensive, $cheap) {
                $ids = array_column($params['pins'], 'id');

                return $ids === [$expensive->id] && ! in_array($cheap->id, $ids, true);
            });
    }

    /**
     * Доработка по просьбе пользователя: новые фильтры (площадь,
     * особенности помещения) тоже должны обновлять пины на карте сразу,
     * как и уже существовавшие.
     */
    public function test_residential_pins_updated_event_reflects_area_filter_change(): void
    {
        $small = ResidentialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'area' => 30]);
        $large = ResidentialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'area' => 100]);

        Livewire::test(Search::class)
            ->set('view', 'map')
            ->set('areaMin', 50)
            ->assertDispatched('catalog:pins-updated', function (string $name, array $params) use ($large) {
                return count($params['pins']) === 1 && $params['pins'][0]['id'] === $large->id;
            });
    }

    public function test_residential_pins_updated_event_reflects_floor_feature_filter_change(): void
    {
        $withFeature = ResidentialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'floor_features' => ['no_elevator']]);
        $withoutFeature = ResidentialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'floor_features' => []]);

        Livewire::test(Search::class)
            ->set('view', 'map')
            ->set('floorFeatures', ['no_elevator'])
            ->assertDispatched('catalog:pins-updated', function (string $name, array $params) use ($withFeature) {
                return count($params['pins']) === 1 && $params['pins'][0]['id'] === $withFeature->id;
            });
    }

    // --- Коммерческая недвижимость ---

    public function test_commercial_pins_updated_event_reflects_deal_type_filter_change(): void
    {
        $sale = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active']);
        CommercialSaleDetail::factory()->create(['property_id' => $sale->id]);
        $rent = CommercialProperty::factory()->create(['deal_type' => 'rent', 'status' => 'active']);
        CommercialRentDetail::factory()->create(['property_id' => $rent->id]);

        Livewire::test(CommercialSearch::class)
            ->set('view', 'map')
            ->assertDispatched('catalog:pins-updated', function (string $name, array $params) use ($sale) {
                return count($params['pins']) === 1 && $params['pins'][0]['id'] === $sale->id;
            })
            ->set('dealType', 'rent')
            ->assertDispatched('catalog:pins-updated', function (string $name, array $params) use ($rent) {
                return count($params['pins']) === 1 && $params['pins'][0]['id'] === $rent->id;
            });
    }

    public function test_commercial_pins_updated_event_reflects_purpose_type_filter_change(): void
    {
        $office = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'purpose_type' => 'office']);
        CommercialSaleDetail::factory()->create(['property_id' => $office->id]);
        $retail = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'purpose_type' => 'retail']);
        CommercialSaleDetail::factory()->create(['property_id' => $retail->id]);

        Livewire::test(CommercialSearch::class)
            ->set('view', 'map')
            ->set('purposeType', 'retail')
            ->assertDispatched('catalog:pins-updated', function (string $name, array $params) use ($retail) {
                return count($params['pins']) === 1 && $params['pins'][0]['id'] === $retail->id;
            });
    }

    public function test_commercial_pins_updated_event_reflects_building_type_filter_change(): void
    {
        $businessCenter = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'building_type' => 'business_center']);
        CommercialSaleDetail::factory()->create(['property_id' => $businessCenter->id]);
        $shoppingCenter = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'building_type' => 'shopping_center']);
        CommercialSaleDetail::factory()->create(['property_id' => $shoppingCenter->id]);

        Livewire::test(CommercialSearch::class)
            ->set('view', 'map')
            ->set('dealType', 'sale')
            ->set('buildingType', 'business_center')
            ->assertDispatched('catalog:pins-updated', function (string $name, array $params) use ($businessCenter) {
                return count($params['pins']) === 1 && $params['pins'][0]['id'] === $businessCenter->id;
            });
    }

    public function test_commercial_pins_updated_event_reflects_area_filter_change(): void
    {
        $small = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'area' => 50]);
        CommercialSaleDetail::factory()->create(['property_id' => $small->id]);
        $large = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'area' => 500]);
        CommercialSaleDetail::factory()->create(['property_id' => $large->id]);

        Livewire::test(CommercialSearch::class)
            ->set('view', 'map')
            ->set('dealType', 'sale')
            ->set('areaMin', 200)
            ->assertDispatched('catalog:pins-updated', function (string $name, array $params) use ($large) {
                return count($params['pins']) === 1 && $params['pins'][0]['id'] === $large->id;
            });
    }

    public function test_commercial_pins_updated_event_reflects_floor_feature_filter_change(): void
    {
        $withParking = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'floor_features' => ['parking']]);
        CommercialSaleDetail::factory()->create(['property_id' => $withParking->id]);
        $withoutParking = CommercialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active', 'floor_features' => ['security']]);
        CommercialSaleDetail::factory()->create(['property_id' => $withoutParking->id]);

        Livewire::test(CommercialSearch::class)
            ->set('view', 'map')
            ->set('dealType', 'sale')
            ->set('floorFeatures', ['parking'])
            ->assertDispatched('catalog:pins-updated', function (string $name, array $params) use ($withParking) {
                return count($params['pins']) === 1 && $params['pins'][0]['id'] === $withParking->id;
            });
    }

    // --- Рабочие пространства ---

    public function test_workspace_pins_updated_event_reflects_workspace_type_filter_change(): void
    {
        $workspace = Workspace::factory()->create(['status' => 'active', 'workspace_type' => 'workspace']);
        WorkspacePricing::factory()->create(['workspace_id' => $workspace->id, 'period' => 'day', 'price' => 1500]);
        $office = Workspace::factory()->create(['status' => 'active', 'workspace_type' => 'office']);
        WorkspacePricing::factory()->create(['workspace_id' => $office->id, 'period' => 'day', 'price' => 2500]);

        Livewire::test(WorkspaceSearch::class)
            ->set('view', 'map')
            ->assertDispatched('catalog:pins-updated', function (string $name, array $params) {
                return count($params['pins']) === 2;
            })
            ->set('workspaceType', 'office')
            ->assertDispatched('catalog:pins-updated', function (string $name, array $params) use ($office) {
                return count($params['pins']) === 1 && $params['pins'][0]['id'] === $office->id;
            });
    }

    public function test_workspace_pins_updated_event_reflects_price_filter_change(): void
    {
        $cheap = Workspace::factory()->create(['status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $cheap->id, 'period' => 'day', 'price' => 1000]);
        $expensive = Workspace::factory()->create(['status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $expensive->id, 'period' => 'day', 'price' => 5000]);

        Livewire::test(WorkspaceSearch::class)
            ->set('view', 'map')
            ->set('period', 'day')
            ->set('priceMin', 3000)
            ->assertDispatched('catalog:pins-updated', function (string $name, array $params) use ($expensive) {
                return count($params['pins']) === 1 && $params['pins'][0]['id'] === $expensive->id;
            });
    }

    /**
     * Доработка по просьбе пользователя: чекбоксы поиска по удобствам
     * заменены на чекбоксы по особенностям помещения — новый фильтр тоже
     * должен обновлять пины на карте.
     */
    public function test_workspace_pins_updated_event_reflects_floor_feature_filter_change(): void
    {
        $withParking = Workspace::factory()->create(['status' => 'active', 'floor_features' => ['parking']]);
        WorkspacePricing::factory()->create(['workspace_id' => $withParking->id, 'period' => 'day', 'price' => 2000]);
        $withoutParking = Workspace::factory()->create(['status' => 'active', 'floor_features' => ['reception']]);
        WorkspacePricing::factory()->create(['workspace_id' => $withoutParking->id, 'period' => 'day', 'price' => 2000]);

        Livewire::test(WorkspaceSearch::class)
            ->set('view', 'map')
            ->set('floorFeatures', ['parking'])
            ->assertDispatched('catalog:pins-updated', function (string $name, array $params) use ($withParking) {
                return count($params['pins']) === 1 && $params['pins'][0]['id'] === $withParking->id;
            });
    }

    /**
     * Событие должно диспатчиться независимо от того, в каком $view сейчас
     * находится компонент (список/карта) — карта может быть открыта уже
     * ПОСЛЕ изменения фильтра, и в этот момент у неё должны быть свежие
     * данные, а не пины на момент последнего открытия вкладки "Карта".
     */
    public function test_pins_updated_event_carries_fresh_data_even_when_filter_changed_while_in_list_view(): void
    {
        $sale = ResidentialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active']);
        $rent = ResidentialProperty::factory()->create(['deal_type' => 'rent', 'status' => 'active']);

        Livewire::test(Search::class)
            ->assertSet('view', 'list')
            ->set('dealType', 'rent')
            ->assertDispatched('catalog:pins-updated', function (string $name, array $params) use ($rent) {
                return count($params['pins']) === 1 && $params['pins'][0]['id'] === $rent->id;
            })
            ->set('view', 'map')
            ->assertDispatched('catalog:pins-updated', function (string $name, array $params) use ($rent) {
                return count($params['pins']) === 1 && $params['pins'][0]['id'] === $rent->id;
            });
    }
}
