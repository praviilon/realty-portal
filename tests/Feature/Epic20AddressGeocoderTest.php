<?php

namespace Tests\Feature;

use App\Livewire\CommercialProperty\CreateWizard as CommercialCreateWizard;
use App\Livewire\Property\CreateWizard as ResidentialCreateWizard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Эпик 20 (Веха 2): выбор адреса при создании объявления (геокодер).
 * Сам геокодер/карта — JS, работающий с внешним Yandex Geocoder API
 * (см. resources/js/address-geocoder.js) — здесь недоступен для проверки
 * (песочница без доступа в интернет). Тестируется то, что действительно
 * решается на бэкенде: компонент подбора адреса корректно встроен в оба
 * мастера создания объявлений, показывает верный fallback без API-ключа
 * и не ломает существующий шаг "Адрес" (address/lat/lng по-прежнему
 * обычные Livewire-свойства, как до эпика).
 */
class Epic20AddressGeocoderTest extends TestCase
{
    use RefreshDatabase;

    public function test_residential_wizard_shows_fallback_without_api_key(): void
    {
        Config::set('services.yandex_maps.api_key', null);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ResidentialCreateWizard::class)
            ->set('step', 2)
            ->assertSee('YANDEX_MAPS_API_KEY')
            ->assertDontSee('addressGeocoder(', false);
    }

    public function test_residential_wizard_renders_address_picker_with_api_key(): void
    {
        Config::set('services.yandex_maps.api_key', 'test-fake-key');
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ResidentialCreateWizard::class)
            ->set('step', 2)
            ->assertSee('addressGeocoder(', false)
            ->assertDontSee('YANDEX_MAPS_API_KEY');
    }

    public function test_commercial_wizard_shows_fallback_without_api_key(): void
    {
        Config::set('services.yandex_maps.api_key', null);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommercialCreateWizard::class)
            ->set('step', 2)
            ->assertSee('YANDEX_MAPS_API_KEY')
            ->assertDontSee('addressGeocoder(', false);
    }

    public function test_commercial_wizard_renders_address_picker_with_api_key(): void
    {
        Config::set('services.yandex_maps.api_key', 'test-fake-key');
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommercialCreateWizard::class)
            ->set('step', 2)
            ->assertSee('addressGeocoder(', false)
            ->assertDontSee('YANDEX_MAPS_API_KEY');
    }

    public function test_existing_address_and_coordinates_are_passed_to_picker_on_edit(): void
    {
        Config::set('services.yandex_maps.api_key', 'test-fake-key');
        $user = User::factory()->create();
        $listing = \App\Models\ResidentialProperty::factory()->create([
            'user_id' => $user->id,
            'address' => 'ул. Существующая, 42',
            'lat' => 55.751244,
            'lng' => 37.618423,
        ]);

        Livewire::actingAs($user)
            ->test(ResidentialCreateWizard::class, ['residentialProperty' => $listing])
            ->set('step', 2)
            ->assertSee('ул. Существующая, 42', false)
            ->assertSee('55.751244', false)
            ->assertSee('37.618423', false);
    }

    public function test_wizard_still_advances_past_address_step_with_picker_rendered(): void
    {
        Config::set('services.yandex_maps.api_key', 'test-fake-key');
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ResidentialCreateWizard::class)
            ->set('dealType', 'sale')
            ->set('propertyType', 'apartment')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.751244)
            ->set('lng', 37.618423)
            ->call('nextStep')
            ->assertSet('step', 3);
    }
}
