<?php

namespace Tests\Feature;

use App\Livewire\Catalog\Search;
use App\Models\ResidentialProperty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Эпик 19 (Веха 2): выделение области на карте. Тестовое окружение (см.
 * phpunit.xml) работает на sqlite, где нет MySQL ST_Contains/SPATIAL INDEX —
 * поэтому здесь проверяется PHP-фолбэк (ray casting) в
 * App\Livewire\Catalog\Search::applyAreaFilter/pointInPolygon, который на
 * sqlite даёт тот же результат, что и ST_Contains на MySQL в проде (см.
 * комментарий в миграции 2024_02_04_000001_add_location_point...).
 */
class Epic19MapAreaSelectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Небольшой квадрат вокруг центра Москвы (широта/долгота), используем
     * как "выделенную область" в тестах ниже.
     */
    private function squareAroundMoscow(): array
    {
        return [
            ['lat' => 55.70, 'lng' => 37.55],
            ['lat' => 55.70, 'lng' => 37.70],
            ['lat' => 55.85, 'lng' => 37.70],
            ['lat' => 55.85, 'lng' => 37.55],
        ];
    }

    public function test_listings_inside_selected_area_are_shown(): void
    {
        $inside = ResidentialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'address' => 'ул. Внутри области, 1',
            'lat' => 55.75,
            'lng' => 37.62,
        ]);

        $outside = ResidentialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'address' => 'ул. Вне области, 2',
            'lat' => 59.93, // Санкт-Петербург — далеко за пределами квадрата
            'lng' => 30.34,
        ]);

        Livewire::test(Search::class)
            ->set('dealType', 'sale')
            ->call('applyAreaSelection', $this->squareAroundMoscow())
            ->assertSee('ул. Внутри области, 1')
            ->assertDontSee('ул. Вне области, 2');
    }

    public function test_clear_area_selection_shows_all_listings_again(): void
    {
        ResidentialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'address' => 'ул. Вне области, 2',
            'lat' => 59.93,
            'lng' => 30.34,
        ]);

        Livewire::test(Search::class)
            ->set('dealType', 'sale')
            ->call('applyAreaSelection', $this->squareAroundMoscow())
            ->assertDontSee('ул. Вне области, 2')
            ->call('clearAreaSelection')
            ->assertSee('ул. Вне области, 2');
    }

    public function test_area_selection_combines_with_other_filters(): void
    {
        $matchingType = ResidentialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'property_type' => 'house',
            'address' => 'ул. Дом в области, 3',
            'lat' => 55.75,
            'lng' => 37.62,
        ]);

        $wrongType = ResidentialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'property_type' => 'apartment',
            'address' => 'ул. Квартира в области, 4',
            'lat' => 55.75,
            'lng' => 37.62,
        ]);

        Livewire::test(Search::class)
            ->set('dealType', 'sale')
            ->set('propertyType', 'house')
            ->call('applyAreaSelection', $this->squareAroundMoscow())
            ->assertSee('ул. Дом в области, 3')
            ->assertDontSee('ул. Квартира в области, 4');
    }

    public function test_area_polygon_with_fewer_than_three_points_is_ignored(): void
    {
        ResidentialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'address' => 'ул. Вне области, 2',
            'lat' => 59.93,
            'lng' => 30.34,
        ]);

        Livewire::test(Search::class)
            ->set('dealType', 'sale')
            ->call('applyAreaSelection', [['lat' => 55.70, 'lng' => 37.55], ['lat' => 55.85, 'lng' => 37.70]])
            ->assertSee('ул. Вне области, 2');
    }

    public function test_catalog_page_loads_with_map_view_and_area_selection_ui(): void
    {
        Config::set('services.yandex_maps.api_key', 'test-fake-key');

        Livewire::test(Search::class)
            ->set('view', 'map')
            ->assertSee('Выделить область на карте', false);
    }
}
