<?php

namespace Tests\Feature;

use App\Models\ResidentialProperty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Доработка по просьбе пользователя: на странице объявления жилой
 * недвижимости добавлены характеристики "Отделка"/"Отопление"/"Мебель" (те
 * же варианты, что и у коммерческой недвижимости), чекбокс "Нет лифта" в
 * "особенностях помещения" и станция метро/минуты пешком (по аналогии с
 * рабочими пространствами). См. resources/views/livewire/property/show.blade.php.
 */
class ResidentialShowNewCharacteristicsTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_displays_metro_station_and_distance(): void
    {
        $listing = ResidentialProperty::factory()->create([
            'status' => 'active',
            'metro_station' => 'Тверская',
            'metro_distance_min' => 7,
        ]);

        $this->get(route('residential.show', $listing))
            ->assertOk()
            ->assertSee('Тверская')
            ->assertSee('7 мин пешком');
    }

    public function test_show_page_hides_metro_block_when_not_set(): void
    {
        $listing = ResidentialProperty::factory()->create([
            'status' => 'active',
            'metro_station' => null,
            'metro_distance_min' => null,
        ]);

        $this->get(route('residential.show', $listing))
            ->assertOk()
            ->assertDontSee('мин пешком');
    }

    public function test_show_page_displays_heating_finishing_and_furniture(): void
    {
        $listing = ResidentialProperty::factory()->create([
            'status' => 'active',
            'heating_type' => 'autonomous',
            'finishing_type' => 'rough',
            'furniture' => 'partial',
        ]);

        $this->get(route('residential.show', $listing))
            ->assertOk()
            ->assertSee('Отопление')
            ->assertSee('Автономное')
            ->assertSee('Отделка')
            ->assertSee('Черновая')
            ->assertSee('Мебель')
            ->assertSee('Частично');
    }

    public function test_show_page_hides_heating_finishing_furniture_when_null(): void
    {
        $listing = ResidentialProperty::factory()->create([
            'status' => 'active',
            'heating_type' => null,
            'finishing_type' => null,
            'furniture' => null,
        ]);

        $this->get(route('residential.show', $listing))
            ->assertOk()
            ->assertDontSee('Отопление')
            ->assertDontSee('Отделка')
            ->assertDontSee('Мебель');
    }

    public function test_show_page_displays_no_elevator_floor_feature(): void
    {
        $listing = ResidentialProperty::factory()->create([
            'status' => 'active',
            'floor_features' => ['no_elevator'],
        ]);

        $this->get(route('residential.show', $listing))
            ->assertOk()
            ->assertSee('Особенности помещения')
            ->assertSee('Нет лифта');
    }

    public function test_show_page_hides_floor_features_block_when_empty(): void
    {
        $listing = ResidentialProperty::factory()->create([
            'status' => 'active',
            'floor_features' => [],
        ]);

        $this->get(route('residential.show', $listing))
            ->assertOk()
            ->assertDontSee('Особенности помещения');
    }

    /**
     * Защита от "битых" значений floor_features (например, если в будущем
     * какое-то значение уберут из ResidentialProperty::floorFeatureLabels(),
     * как это уже произошло с Workspace::separate_entrance) — страница не
     * должна падать и не должна отображать несуществующую особенность.
     */
    public function test_show_page_does_not_display_unknown_floor_feature_and_does_not_break(): void
    {
        $listing = ResidentialProperty::factory()->create([
            'status' => 'active',
            'floor_features' => ['has_pool'],
        ]);

        $this->get(route('residential.show', $listing))
            ->assertOk()
            ->assertDontSee('has_pool')
            ->assertDontSee('Особенности помещения');
    }
}
