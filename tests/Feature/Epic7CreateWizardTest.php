<?php

namespace Tests\Feature;

use App\Models\ResidentialProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class Epic7CreateWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('residential.create'))->assertRedirect(route('login'));
    }

    public function test_user_can_create_listing_through_all_steps(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->set('dealType', 'sale')
            ->set('propertyType', 'apartment')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.751244)
            ->set('lng', 37.618423)
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('area', 45)
            ->set('floor', 3)
            ->set('totalFloors', 9)
            ->set('description', 'Отличная квартира рядом с метро.')
            ->call('nextStep')
            ->assertSet('step', 4)
            ->set('price', 6500000)
            ->set('commission', 50000)
            ->call('nextStep')
            ->assertSet('step', 5)
            ->call('submit')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('residential_properties', [
            'user_id' => $user->id,
            'address' => 'г. Москва, ул. Тестовая, д. 5',
            'price' => 6500000,
            'commission' => 50000,
            'deposit' => null,
            'rent_type' => null,
            'utilities_included' => false,
            'status' => 'moderation',
        ]);
    }

    /**
     * Доработка по просьбе пользователя: шаг 4 ("Цена") вынесен из шага 3 в
     * отдельный шаг по аналогии с коммерческой недвижимостью — для аренды
     * нужны цена в месяц/депозит/комиссия/тип аренды/"коммунальные платежи
     * включены".
     */
    public function test_user_can_create_rent_listing_with_rent_specific_price_fields(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->set('dealType', 'rent')
            ->set('propertyType', 'apartment')
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.751244)
            ->set('lng', 37.618423)
            ->call('nextStep')
            ->set('area', 45)
            ->set('floor', 3)
            ->set('totalFloors', 9)
            ->set('description', 'Отличная квартира рядом с метро.')
            ->call('nextStep')
            ->assertSet('step', 4)
            ->set('pricePerMonth', 80000)
            ->set('deposit', 80000)
            ->set('commission', 40000)
            ->set('rentType', 'sublease')
            ->set('utilitiesIncluded', true)
            ->call('nextStep')
            ->assertSet('step', 5)
            ->call('submit')
            ->assertRedirect(route('dashboard'));

        $listing = ResidentialProperty::first();
        $this->assertNotNull($listing);
        $this->assertSame('rent', $listing->deal_type);
        $this->assertSame(80000, $listing->price);
        $this->assertSame(80000, $listing->deposit);
        $this->assertSame(40000, $listing->commission);
        $this->assertSame('sublease', $listing->rent_type);
        $this->assertTrue($listing->utilities_included);
    }

    public function test_step4_requires_price_per_month_for_rent(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->set('dealType', 'rent')
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.7)
            ->set('lng', 37.6)
            ->call('nextStep')
            ->set('area', 50)
            ->set('floor', 1)
            ->set('totalFloors', 5)
            ->set('description', 'Тестовое описание квартиры.')
            ->call('nextStep')
            ->call('nextStep')
            ->assertHasErrors(['pricePerMonth'])
            ->assertSet('step', 4);
    }

    public function test_step4_requires_price_for_sale(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->set('dealType', 'sale')
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.7)
            ->set('lng', 37.6)
            ->call('nextStep')
            ->set('area', 50)
            ->set('floor', 1)
            ->set('totalFloors', 5)
            ->set('description', 'Тестовое описание квартиры.')
            ->call('nextStep')
            ->call('nextStep')
            ->assertHasErrors(['price'])
            ->assertSet('step', 4);
    }

    /**
     * Доработка по просьбе пользователя: "Отделка"/"Отопление"/"Мебель" (те же
     * варианты, что и у коммерческой недвижимости), чекбокс "Нет лифта" в
     * "особенностях помещения" (шаг 3) и станция метро/минуты пешком (шаг 2,
     * по аналогии с рабочими пространствами).
     */
    public function test_user_can_create_listing_with_new_characteristics_and_metro(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->set('dealType', 'sale')
            ->set('propertyType', 'apartment')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.751244)
            ->set('lng', 37.618423)
            ->set('metroStation', 'Тверская')
            ->set('metroDistanceMin', 6)
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('area', 45)
            ->set('floor', 3)
            ->set('totalFloors', 9)
            ->set('heatingType', 'autonomous')
            ->set('finishingType', 'rough')
            ->set('furniture', 'partial')
            ->set('floorFeatures', ['no_elevator'])
            ->set('description', 'Отличная квартира рядом с метро.')
            ->call('nextStep')
            ->assertSet('step', 4)
            ->set('price', 6500000)
            ->call('nextStep')
            ->assertSet('step', 5)
            ->call('submit')
            ->assertRedirect(route('dashboard'));

        $listing = ResidentialProperty::first();
        $this->assertNotNull($listing);
        $this->assertSame('Тверская', $listing->metro_station);
        $this->assertSame(6, $listing->metro_distance_min);
        $this->assertSame('autonomous', $listing->heating_type);
        $this->assertSame('rough', $listing->finishing_type);
        $this->assertSame('partial', $listing->furniture);
        $this->assertSame(['no_elevator'], $listing->floor_features);
    }

    public function test_metro_fields_are_optional(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.751244)
            ->set('lng', 37.618423)
            ->call('nextStep')
            ->assertHasNoErrors(['metroStation', 'metroDistanceMin'])
            ->assertSet('step', 3);
    }

    public function test_invalid_floor_feature_value_is_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.751244)
            ->set('lng', 37.618423)
            ->call('nextStep')
            ->set('area', 45)
            ->set('floor', 3)
            ->set('totalFloors', 9)
            ->set('floorFeatures', ['has_pool'])
            ->set('description', 'Отличная квартира рядом с метро.')
            ->call('nextStep')
            ->assertHasErrors(['floorFeatures.0'])
            ->assertSet('step', 3);
    }

    public function test_editing_prefills_new_characteristics(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create([
            'user_id' => $user->id,
            'metro_station' => 'Парк Культуры',
            'metro_distance_min' => 10,
            'heating_type' => 'central',
            'finishing_type' => 'fine',
            'furniture' => 'full',
            'floor_features' => ['no_elevator'],
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class, ['residentialProperty' => $listing])
            ->assertSet('metroStation', 'Парк Культуры')
            ->assertSet('metroDistanceMin', 10)
            ->assertSet('heatingType', 'central')
            ->assertSet('finishingType', 'fine')
            ->assertSet('furniture', 'full')
            ->assertSet('floorFeatures', ['no_elevator']);
    }

    public function test_step1_validation_blocks_progress(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->set('propertyType', 'invalid-type')
            ->call('nextStep')
            ->assertHasErrors(['propertyType'])
            ->assertSet('step', 1);
    }

    public function test_step2_validation_blocks_progress(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->call('nextStep') // step 1 -> 2 (defaults are valid)
            ->set('address', '')
            ->call('nextStep')
            ->assertHasErrors(['address'])
            ->assertSet('step', 2);
    }

    public function test_cannot_skip_ahead_via_go_to_step(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->call('goToStep', 5)
            ->assertSet('step', 1);
    }

    public function test_photo_upload_creates_property_photo_with_main_flag(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $photo = UploadedFile::fake()->image('flat.jpg');

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class)
            ->set('dealType', 'sale')
            ->set('propertyType', 'apartment')
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.751244)
            ->set('lng', 37.618423)
            ->call('nextStep')
            ->set('area', 45)
            ->set('floor', 3)
            ->set('totalFloors', 9)
            ->set('description', 'Отличная квартира рядом с метро.')
            ->call('nextStep')
            ->set('price', 6500000)
            ->call('nextStep')
            ->set('newPhotos', [$photo])
            ->call('submit');

        $listing = ResidentialProperty::first();
        $this->assertNotNull($listing);
        $this->assertSame(1, $listing->photos()->count());
        $this->assertTrue($listing->photos()->first()->is_main);
        Storage::disk('public')->assertExists($listing->photos()->first()->path);
    }

    public function test_owner_can_edit_own_listing_and_it_returns_to_moderation(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'deal_type' => 'sale',
            'price' => 1000000,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class, ['residentialProperty' => $listing])
            ->assertSet('address', $listing->address)
            ->assertSet('price', 1000000)
            ->set('price', 2000000)
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->call('submit');

        $listing->refresh();
        $this->assertSame(2000000, $listing->price);
        $this->assertSame('moderation', $listing->status);
    }

    /**
     * Доработка по просьбе пользователя: при смене типа сделки на шаге 1
     * набор полей на шаге 4 меняется — при переключении с продажи на
     * аренду поля продажи (price здесь общая колонка, но rent_type/deposit/
     * utilities_included) должны корректно перезаписаться, по аналогии с
     * App\Livewire\CommercialProperty\CreateWizard.
     */
    public function test_switching_deal_type_on_edit_updates_price_fields(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create([
            'user_id' => $user->id,
            'deal_type' => 'sale',
            'price' => 5000000,
            'commission' => 100000,
            'deposit' => null,
            'rent_type' => null,
            'utilities_included' => false,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Property\CreateWizard::class, ['residentialProperty' => $listing])
            ->set('dealType', 'rent')
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->assertSet('step', 4)
            ->set('pricePerMonth', 90000)
            ->set('deposit', 90000)
            ->set('rentType', 'direct')
            ->set('utilitiesIncluded', true)
            ->call('nextStep')
            ->call('submit');

        $listing->refresh();
        $this->assertSame('rent', $listing->deal_type);
        $this->assertSame(90000, $listing->price);
        $this->assertSame(90000, $listing->deposit);
        $this->assertSame('direct', $listing->rent_type);
        $this->assertTrue($listing->utilities_included);
    }

    public function test_user_cannot_edit_someone_elses_listing(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get(route('residential.edit', $listing))
            ->assertForbidden();
    }
}
