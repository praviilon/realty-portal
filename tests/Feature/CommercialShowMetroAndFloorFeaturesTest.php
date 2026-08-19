<?php

namespace Tests\Feature;

use App\Models\CommercialProperty;
use App\Models\CommercialSaleDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Доработка по просьбе пользователя: на странице объекта коммерческой
 * недвижимости добавлена станция метро/минуты пешком (по аналогии с
 * рабочими пространствами), а особенность "Отдельный вход с улицы" убрана
 * совсем — дублирует характеристику "Вход" (entrance_type). См.
 * resources/views/livewire/commercial-property/show.blade.php и
 * App\Models\CommercialProperty::floorFeatureLabels().
 */
class CommercialShowMetroAndFloorFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_displays_metro_station_and_distance(): void
    {
        $listing = CommercialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'metro_station' => 'Тверская',
            'metro_distance_min' => 8,
        ]);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        $this->get(route('commercial.show', $listing))
            ->assertOk()
            ->assertSee('Тверская')
            ->assertSee('8 мин пешком');
    }

    public function test_show_page_hides_metro_block_when_not_set(): void
    {
        $listing = CommercialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'metro_station' => null,
            'metro_distance_min' => null,
        ]);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        $this->get(route('commercial.show', $listing))
            ->assertOk()
            ->assertDontSee('мин пешком');
    }

    public function test_show_page_displays_remaining_floor_features(): void
    {
        $listing = CommercialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'floor_features' => ['shop_window', 'high_traffic', 'parking', 'security'],
        ]);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        $this->get(route('commercial.show', $listing))
            ->assertOk()
            ->assertSee('Витринные окна')
            ->assertSee('Проходное место')
            ->assertSee('Парковка')
            ->assertSee('Охрана/видеонаблюдение');
    }

    /**
     * Ранее созданные объявления могут ещё хранить 'separate_entrance' в
     * floor_features (значение убрано из
     * CommercialProperty::floorFeatureLabels(), но никакой миграции данных
     * для этого не делалось) — страница не должна падать и не должна
     * показывать эту особенность.
     */
    public function test_show_page_does_not_display_removed_separate_entrance_feature_and_does_not_break(): void
    {
        $listing = CommercialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'floor_features' => ['separate_entrance'],
        ]);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        $this->get(route('commercial.show', $listing))
            ->assertOk()
            ->assertDontSee('Отдельный вход с улицы')
            ->assertDontSee('separate_entrance');
    }

    public function test_show_page_does_not_display_removed_separate_entrance_alongside_valid_features(): void
    {
        $listing = CommercialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'floor_features' => ['separate_entrance', 'parking'],
        ]);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        $this->get(route('commercial.show', $listing))
            ->assertOk()
            ->assertDontSee('Отдельный вход с улицы')
            ->assertSee('Парковка');
    }

    public function test_show_page_hides_floor_features_block_when_empty(): void
    {
        $listing = CommercialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'floor_features' => [],
        ]);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        $this->get(route('commercial.show', $listing))
            ->assertOk()
            ->assertDontSee('Витринные окна')
            ->assertDontSee('Парковка');
    }
}
