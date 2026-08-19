<?php

namespace Tests\Feature;

use App\Livewire\CommercialProperty\CreateWizard;
use App\Models\CommercialProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class Epic13CommercialPropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('commercial.create'))->assertRedirect(route('login'));
    }

    public function test_user_can_create_sale_listing_through_all_steps(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateWizard::class)
            ->set('dealType', 'sale')
            ->set('purposeType', 'office')
            ->set('buildingType', 'business_center')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('address', 'г. Москва, ул. Деловая, д. 10')
            ->set('lat', 55.751244)
            ->set('lng', 37.618423)
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('area', 120)
            ->set('floor', 5)
            ->set('totalFloors', 20)
            ->set('floorFeatures', ['shop_window', 'parking'])
            ->set('description', 'Просторный офис в бизнес-центре класса А.')
            ->call('nextStep')
            ->assertSet('step', 4)
            ->set('price', 25000000)
            ->set('commission', 100000)
            ->call('nextStep')
            ->assertSet('step', 5)
            ->call('submit')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('commercial_properties', [
            'user_id' => $user->id,
            'address' => 'г. Москва, ул. Деловая, д. 10',
            'deal_type' => 'sale',
            'status' => 'moderation',
        ]);

        $listing = CommercialProperty::first();
        $this->assertSame(['shop_window', 'parking'], $listing->floor_features);
        $this->assertNotNull($listing->saleDetail);
        $this->assertSame(25000000, $listing->saleDetail->price);
        $this->assertSame(100000, $listing->saleDetail->commission);
        $this->assertNull($listing->rentDetail);
        $this->assertSame(25000000, $listing->display_price);
    }

    public function test_user_can_create_rent_listing_with_rent_specific_fields(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateWizard::class)
            ->set('dealType', 'rent')
            ->set('purposeType', 'retail')
            ->set('buildingType', 'shopping_center')
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Торговая, д. 1')
            ->set('lat', 55.7)
            ->set('lng', 37.6)
            ->call('nextStep')
            ->set('area', 80)
            ->set('floor', 1)
            ->set('totalFloors', 3)
            ->set('description', 'Помещение свободного назначения на первой линии.')
            ->call('nextStep')
            ->set('pricePerMonth', 350000)
            ->set('deposit', 350000)
            ->set('utilitiesIncluded', true)
            ->set('rentType', 'direct')
            ->call('nextStep')
            ->call('submit');

        $listing = CommercialProperty::first();
        $this->assertNotNull($listing);
        $this->assertSame('rent', $listing->deal_type);
        $this->assertNotNull($listing->rentDetail);
        $this->assertSame(350000, $listing->rentDetail->price_per_month);
        $this->assertTrue($listing->rentDetail->utilities_included);
        $this->assertNull($listing->saleDetail);
        $this->assertSame(350000, $listing->display_price);
    }

    /**
     * Доработка по просьбе пользователя: станция метро/минуты пешком (шаг 2,
     * по аналогии с рабочими пространствами).
     */
    public function test_user_can_create_listing_with_metro_fields(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateWizard::class)
            ->set('dealType', 'sale')
            ->set('purposeType', 'office')
            ->set('buildingType', 'business_center')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('address', 'г. Москва, ул. Деловая, д. 10')
            ->set('lat', 55.751244)
            ->set('lng', 37.618423)
            ->set('metroStation', 'Тверская')
            ->set('metroDistanceMin', 8)
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('area', 120)
            ->set('floor', 5)
            ->set('totalFloors', 20)
            ->set('description', 'Просторный офис в бизнес-центре класса А.')
            ->call('nextStep')
            ->set('price', 25000000)
            ->call('nextStep')
            ->call('submit');

        $listing = CommercialProperty::first();
        $this->assertNotNull($listing);
        $this->assertSame('Тверская', $listing->metro_station);
        $this->assertSame(8, $listing->metro_distance_min);
    }

    public function test_metro_fields_are_optional(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateWizard::class)
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.751244)
            ->set('lng', 37.618423)
            ->call('nextStep')
            ->assertHasNoErrors(['metroStation', 'metroDistanceMin'])
            ->assertSet('step', 3);
    }

    /**
     * Доработка по просьбе пользователя: чекбокс "Отдельный вход с улицы"
     * убран из шага 3 (дублирует характеристику "Вход") — значение больше
     * не проходит валидацию как допустимая особенность помещения.
     */
    public function test_removed_separate_entrance_floor_feature_is_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateWizard::class)
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.751244)
            ->set('lng', 37.618423)
            ->call('nextStep')
            ->set('area', 50)
            ->set('floor', 1)
            ->set('totalFloors', 5)
            ->set('floorFeatures', ['separate_entrance'])
            ->set('description', 'Тестовое описание помещения.')
            ->call('nextStep')
            ->assertHasErrors(['floorFeatures.0'])
            ->assertSet('step', 3);
    }

    public function test_editing_prefills_metro_fields_and_sanitizes_legacy_floor_feature(): void
    {
        $user = User::factory()->create();
        $listing = CommercialProperty::factory()->create([
            'user_id' => $user->id,
            'metro_station' => 'Парк Культуры',
            'metro_distance_min' => 12,
            'floor_features' => ['separate_entrance', 'parking'],
        ]);

        Livewire::actingAs($user)
            ->test(CreateWizard::class, ['commercialProperty' => $listing])
            ->assertSet('metroStation', 'Парк Культуры')
            ->assertSet('metroDistanceMin', 12)
            ->assertSet('floorFeatures', ['parking']);
    }

    public function test_step1_validation_blocks_progress(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateWizard::class)
            ->set('purposeType', 'invalid-type')
            ->call('nextStep')
            ->assertHasErrors(['purposeType'])
            ->assertSet('step', 1);
    }

    public function test_step4_requires_price_per_month_for_rent(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateWizard::class)
            ->set('dealType', 'rent')
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.7)
            ->set('lng', 37.6)
            ->call('nextStep')
            ->set('area', 50)
            ->set('floor', 1)
            ->set('totalFloors', 5)
            ->set('description', 'Тестовое описание помещения.')
            ->call('nextStep')
            ->call('nextStep')
            ->assertHasErrors(['pricePerMonth'])
            ->assertSet('step', 4);
    }

    public function test_cannot_skip_ahead_via_go_to_step(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateWizard::class)
            ->call('goToStep', 5)
            ->assertSet('step', 1);
    }

    public function test_photo_upload_creates_property_photo_with_main_flag(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $photo = UploadedFile::fake()->image('office.jpg');

        Livewire::actingAs($user)
            ->test(CreateWizard::class)
            ->set('dealType', 'sale')
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.7)
            ->set('lng', 37.6)
            ->call('nextStep')
            ->set('area', 50)
            ->set('floor', 1)
            ->set('totalFloors', 5)
            ->set('description', 'Тестовое описание помещения.')
            ->call('nextStep')
            ->set('price', 10000000)
            ->call('nextStep')
            ->set('newPhotos', [$photo])
            ->call('submit');

        $listing = CommercialProperty::first();
        $this->assertNotNull($listing);
        $this->assertSame(1, $listing->photos()->count());
        $this->assertTrue($listing->photos()->first()->is_main);
        Storage::disk('public')->assertExists($listing->photos()->first()->path);
    }

    public function test_owner_can_edit_own_listing_and_it_returns_to_moderation(): void
    {
        $user = User::factory()->create();
        $listing = CommercialProperty::factory()->create([
            'user_id' => $user->id,
            'deal_type' => 'sale',
            'status' => 'active',
        ]);
        \App\Models\CommercialSaleDetail::factory()->create(['property_id' => $listing->id, 'price' => 5000000]);

        Livewire::actingAs($user)
            ->test(CreateWizard::class, ['commercialProperty' => $listing])
            ->assertSet('address', $listing->address)
            ->set('price', 7000000)
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->call('submit');

        $listing->refresh();
        $this->assertSame('moderation', $listing->status);
        $this->assertSame(7000000, $listing->saleDetail->price);
    }

    public function test_switching_deal_type_on_edit_replaces_detail_row(): void
    {
        $user = User::factory()->create();
        $listing = CommercialProperty::factory()->create([
            'user_id' => $user->id,
            'deal_type' => 'sale',
        ]);
        \App\Models\CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        Livewire::actingAs($user)
            ->test(CreateWizard::class, ['commercialProperty' => $listing])
            ->set('dealType', 'rent')
            ->call('nextStep')
            ->call('nextStep')
            ->set('pricePerMonth', 150000)
            ->set('rentType', 'direct')
            ->call('nextStep')
            ->call('submit');

        $listing->refresh();
        $this->assertSame('rent', $listing->deal_type);
        $this->assertNotNull($listing->rentDetail);
        $this->assertNull($listing->saleDetail);
    }

    public function test_user_cannot_edit_someone_elses_listing(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $listing = CommercialProperty::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get(route('commercial.edit', $listing))
            ->assertForbidden();
    }

    public function test_dashboard_shows_commercial_listing(): void
    {
        $user = User::factory()->create();
        $listing = CommercialProperty::factory()->create([
            'user_id' => $user->id,
            'deal_type' => 'sale',
            'address' => 'г. Москва, ул. Витринная, д. 2',
        ]);
        \App\Models\CommercialSaleDetail::factory()->create(['property_id' => $listing->id, 'price' => 12000000]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('г. Москва, ул. Витринная, д. 2');
    }
}
