<?php

namespace Tests\Feature;

use App\Livewire\Workspace\CreateWizard;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class Epic23WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('workspace.create'))->assertRedirect(route('login'));
    }

    public function test_user_can_create_workspace_listing_through_all_steps(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateWizard::class)
            ->set('workspaceType', 'workspace')
            ->set('workspaceSubtype', 'flexible')
            ->set('ownerType', 'owner')
            ->set('contactType', 'calls_and_messages')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('address', 'г. Москва, ул. Коворкинговая, д. 3')
            ->set('lat', 55.751244)
            ->set('lng', 37.618423)
            ->set('metroStation', 'Тверская')
            ->set('metroDistanceMin', 5)
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('area', 15)
            ->set('buildingType', 'business_center')
            ->set('floor', 3)
            ->set('totalFloors', 10)
            ->set('floorFeatures', ['reception'])
            ->set('amenities', ['wifi', 'coffee'])
            ->set('extraOptions', ['cleaning'])
            ->set('description', 'Уютное рабочее место в коворкинге в центре города.')
            ->call('nextStep')
            ->assertSet('step', 4)
            ->set('pricing.0.period', 'hour')
            ->set('pricing.0.price', 500)
            ->call('nextStep')
            ->assertSet('step', 5)
            ->call('submit')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('workspaces', [
            'user_id' => $user->id,
            'address' => 'г. Москва, ул. Коворкинговая, д. 3',
            'workspace_type' => 'workspace',
            'workspace_subtype' => 'flexible',
            'status' => 'moderation',
        ]);

        $listing = Workspace::first();
        $this->assertSame(['wifi', 'coffee'], $listing->amenities);
        $this->assertSame(['cleaning'], $listing->extra_options);
        $this->assertSame(1, $listing->pricing()->count());
        $this->assertSame(500, $listing->display_price);
        $this->assertSame('hour', $listing->cheapestPricing->period);
    }

    public function test_user_can_create_listing_with_multiple_pricing_periods(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateWizard::class)
            ->set('workspaceType', 'office')
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Офисная, д. 7')
            ->set('lat', 55.7)
            ->set('lng', 37.6)
            ->call('nextStep')
            ->set('area', 40)
            ->set('floor', 2)
            ->set('totalFloors', 5)
            ->set('description', 'Отдельный офис для небольшой команды.')
            ->call('nextStep')
            ->set('pricing.0.period', 'day')
            ->set('pricing.0.price', 5000)
            ->call('addPricingRow')
            ->set('pricing.1.period', 'month')
            ->set('pricing.1.price', 80000)
            ->call('nextStep')
            ->call('submit');

        $listing = Workspace::first();
        $this->assertNotNull($listing);
        $this->assertSame(2, $listing->pricing()->count());
        // Дешевле по дням/суткам, но 80000 за месяц — не самая дешёвая ставка per se;
        // display_price должен выбрать минимальное числовое значение цены (5000).
        $this->assertSame(5000, $listing->display_price);
        // Рабочее пространство не типа "workspace" — подтип должен остаться пустым.
        $this->assertNull($listing->workspace_subtype);
    }

    public function test_duplicate_pricing_periods_are_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateWizard::class)
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.7)
            ->set('lng', 37.6)
            ->call('nextStep')
            ->set('area', 20)
            ->set('floor', 1)
            ->set('totalFloors', 5)
            ->set('description', 'Тестовое описание рабочего пространства.')
            ->call('nextStep')
            ->set('pricing.0.period', 'hour')
            ->set('pricing.0.price', 500)
            ->call('addPricingRow')
            ->set('pricing.1.period', 'hour')
            ->set('pricing.1.price', 400)
            ->call('nextStep')
            ->assertHasErrors(['pricing.0.period', 'pricing.1.period'])
            ->assertSet('step', 4);
    }

    public function test_step1_validation_blocks_progress(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateWizard::class)
            ->set('workspaceType', 'invalid-type')
            ->call('nextStep')
            ->assertHasErrors(['workspaceType'])
            ->assertSet('step', 1);
    }

    public function test_step4_requires_at_least_one_priced_row(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateWizard::class)
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.7)
            ->set('lng', 37.6)
            ->call('nextStep')
            ->set('area', 20)
            ->set('floor', 1)
            ->set('totalFloors', 5)
            ->set('description', 'Тестовое описание рабочего пространства.')
            ->call('nextStep')
            ->set('pricing.0.price', null)
            ->call('nextStep')
            ->assertHasErrors(['pricing.0.price'])
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
        $photo = UploadedFile::fake()->image('workspace.jpg');

        Livewire::actingAs($user)
            ->test(CreateWizard::class)
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.7)
            ->set('lng', 37.6)
            ->call('nextStep')
            ->set('area', 20)
            ->set('floor', 1)
            ->set('totalFloors', 5)
            ->set('description', 'Тестовое описание рабочего пространства.')
            ->call('nextStep')
            ->set('pricing.0.price', 500)
            ->call('nextStep')
            ->set('newPhotos', [$photo])
            ->call('submit');

        $listing = Workspace::first();
        $this->assertNotNull($listing);
        $this->assertSame(1, $listing->photos()->count());
        $this->assertTrue($listing->photos()->first()->is_main);
        Storage::disk('public')->assertExists($listing->photos()->first()->path);
    }

    public function test_owner_can_edit_own_listing_and_it_returns_to_moderation(): void
    {
        $user = User::factory()->create();
        $listing = Workspace::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);
        \App\Models\WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'hour', 'price' => 500]);

        Livewire::actingAs($user)
            ->test(CreateWizard::class, ['workspace' => $listing])
            ->assertSet('address', $listing->address)
            ->call('nextStep')
            ->call('nextStep')
            ->set('pricing.0.price', 700)
            ->call('nextStep')
            ->call('submit');

        $listing->refresh();
        $this->assertSame('moderation', $listing->status);
        $this->assertSame(1, $listing->pricing()->count());
        $this->assertSame(700, $listing->display_price);
    }

    public function test_editing_replaces_pricing_rows_cleanly(): void
    {
        $user = User::factory()->create();
        $listing = Workspace::factory()->create(['user_id' => $user->id]);
        \App\Models\WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'hour', 'price' => 500]);

        Livewire::actingAs($user)
            ->test(CreateWizard::class, ['workspace' => $listing])
            ->call('nextStep')
            ->call('nextStep')
            ->set('pricing.0.period', 'day')
            ->set('pricing.0.price', 4000)
            ->call('addPricingRow')
            ->set('pricing.1.period', 'week')
            ->set('pricing.1.price', 20000)
            ->call('nextStep')
            ->call('submit');

        $listing->refresh();
        $this->assertSame(2, $listing->pricing()->count());
        $this->assertNull($listing->pricing()->where('period', 'hour')->first());
    }

    public function test_user_cannot_edit_someone_elses_listing(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $listing = Workspace::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get(route('workspace.edit', $listing))
            ->assertForbidden();
    }

    /**
     * Доработка по просьбе пользователя: "Отдельный вход с улицы" убран из
     * "Особенностей помещения" на шаге 3 — дублирует характеристику "Вход"
     * (entranceType). См. App\Models\Workspace::floorFeatureLabels().
     */
    public function test_step3_no_longer_offers_separate_entrance_checkbox(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateWizard::class)
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.7)
            ->set('lng', 37.6)
            ->call('nextStep')
            ->assertSet('step', 3)
            ->assertDontSee('Отдельный вход с улицы')
            ->assertSee('Парковка')
            ->assertSee('Охрана/видеонаблюдение')
            ->assertSee('Ресепшн');
    }

    public function test_separate_entrance_is_rejected_as_a_floor_feature_value(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateWizard::class)
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Тестовая, д. 5')
            ->set('lat', 55.7)
            ->set('lng', 37.6)
            ->call('nextStep')
            ->set('area', 20)
            ->set('floor', 1)
            ->set('totalFloors', 5)
            ->set('floorFeatures', ['separate_entrance'])
            ->set('description', 'Тестовое описание рабочего пространства.')
            ->call('nextStep')
            ->assertHasErrors(['floorFeatures.0'])
            ->assertSet('step', 3);
    }

    /**
     * Ранее созданные объявления могут ещё хранить 'separate_entrance' в
     * floor_features — при открытии на редактирование это значение должно
     * молча вычищаться из формы, иначе повторное сохранение без изменения
     * этого поля упало бы на валидации (значения больше нет в списке
     * допустимых).
     */
    public function test_editing_listing_with_legacy_separate_entrance_feature_sanitizes_it_and_still_saves(): void
    {
        $user = User::factory()->create();
        $listing = Workspace::factory()->create([
            'user_id' => $user->id,
            'floor_features' => ['separate_entrance', 'reception'],
        ]);
        \App\Models\WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'hour', 'price' => 500]);

        Livewire::actingAs($user)
            ->test(CreateWizard::class, ['workspace' => $listing])
            ->assertSet('floorFeatures', ['reception'])
            ->call('nextStep')
            ->call('nextStep')
            ->call('nextStep')
            ->call('submit')
            ->assertRedirect(route('dashboard'));

        $listing->refresh();
        $this->assertSame(['reception'], $listing->floor_features);
    }

    public function test_dashboard_shows_workspace_listing(): void
    {
        $user = User::factory()->create();
        $listing = Workspace::factory()->create([
            'user_id' => $user->id,
            'address' => 'г. Москва, ул. Пространственная, д. 4',
        ]);
        \App\Models\WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'hour', 'price' => 600]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('г. Москва, ул. Пространственная, д. 4');
    }
}
