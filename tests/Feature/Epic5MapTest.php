<?php

namespace Tests\Feature;

use App\Models\ResidentialProperty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class Epic5MapTest extends TestCase
{
    use RefreshDatabase;

    public function test_pins_match_filtered_listings(): void
    {
        $shown = ResidentialProperty::factory()->create([
            'deal_type' => 'sale',
            'status' => 'active',
            'lat' => 55.751244,
            'lng' => 37.618423,
        ]);
        ResidentialProperty::factory()->create(['deal_type' => 'rent', 'status' => 'active']);
        ResidentialProperty::factory()->moderation()->create(['deal_type' => 'sale']);

        Livewire::test(\App\Livewire\Catalog\Search::class)
            ->set('dealType', 'sale')
            ->assertViewHas('pins', function (array $pins) use ($shown) {
                return count($pins) === 1
                    && $pins[0]['id'] === $shown->id
                    && $pins[0]['lat'] === 55.751244
                    && $pins[0]['lng'] === 37.618423
                    && $pins[0]['address'] === $shown->address;
            });
    }

    public function test_map_placeholder_shown_without_api_key(): void
    {
        Config::set('services.yandex_maps.api_key', null);

        Livewire::test(\App\Livewire\Catalog\Search::class)
            ->set('view', 'map')
            ->assertSee('YANDEX_MAPS_API_KEY');
    }

    public function test_map_container_renders_with_api_key_configured(): void
    {
        Config::set('services.yandex_maps.api_key', 'test-fake-key');
        ResidentialProperty::factory()->create(['deal_type' => 'sale', 'status' => 'active']);

        Livewire::test(\App\Livewire\Catalog\Search::class)
            ->set('view', 'map')
            ->assertSee('yandexMap(', false)
            ->assertDontSee('YANDEX_MAPS_API_KEY');
    }

    public function test_view_toggle_switches_between_list_and_map(): void
    {
        Livewire::test(\App\Livewire\Catalog\Search::class)
            ->assertSet('view', 'list')
            ->call('$set', 'view', 'map')
            ->assertSet('view', 'map');
    }
}
