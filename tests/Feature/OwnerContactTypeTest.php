<?php

namespace Tests\Feature;

use App\Livewire\CommercialProperty\CreateWizard as CommercialPropertyWizard;
use App\Livewire\Property\CreateWizard as ResidentialPropertyWizard;
use App\Models\CommercialProperty;
use App\Models\CommercialSaleDetail;
use App\Models\ResidentialProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Доработка по просьбе пользователя: на страницах жилой и коммерческой
 * недвижимости тот, кто разместил объявление, всегда отображался как
 * "продавец". Добавлены поля "Кто сдаёт"/"Кто продаёт" и "Как связываться"
 * на шаг 1 обоих мастеров (по аналогии с App\Livewire\Workspace\CreateWizard),
 * и заголовок на странице объекта теперь зависит от owner_type/deal_type —
 * см. resources/views/livewire/property/show.blade.php и
 * resources/views/livewire/commercial-property/show.blade.php.
 */
class OwnerContactTypeTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Жилая недвижимость: шаг 1 мастера ----------

    public function test_residential_step1_shows_renter_label_for_rent_and_seller_label_for_sale(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ResidentialPropertyWizard::class)
            ->set('dealType', 'rent')
            ->assertSee('Кто сдаёт')
            ->set('dealType', 'sale')
            ->assertSee('Кто продаёт');
    }

    public function test_residential_wizard_creates_listing_with_agent_and_messages_only(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ResidentialPropertyWizard::class)
            ->set('dealType', 'rent')
            ->set('propertyType', 'apartment')
            ->set('ownerType', 'agent')
            ->set('contactType', 'messages_only')
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
            ->set('pricePerMonth', 80000)
            ->call('nextStep')
            ->call('submit')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('residential_properties', [
            'user_id' => $user->id,
            'owner_type' => 'agent',
            'contact_type' => 'messages_only',
        ]);
    }

    public function test_residential_step1_rejects_invalid_owner_and_contact_type(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ResidentialPropertyWizard::class)
            ->set('ownerType', 'invalid')
            ->set('contactType', 'invalid')
            ->call('nextStep')
            ->assertHasErrors(['ownerType', 'contactType'])
            ->assertSet('step', 1);
    }

    public function test_residential_wizard_defaults_satisfy_validation_without_touching_new_fields(): void
    {
        // Регрессия: существующие тесты не заполняют ownerType/contactType —
        // значения по умолчанию ('owner'/'calls_and_messages') должны
        // проходить валидацию наравне с остальными шагами.
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ResidentialPropertyWizard::class)
            ->call('nextStep')
            ->assertHasNoErrors(['ownerType', 'contactType'])
            ->assertSet('step', 2);
    }

    public function test_residential_wizard_edit_prefills_owner_and_contact_type(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create([
            'user_id' => $user->id,
            'owner_type' => 'agent',
            'contact_type' => 'messages_only',
        ]);

        Livewire::actingAs($user)
            ->test(ResidentialPropertyWizard::class, ['residentialProperty' => $listing])
            ->assertSet('ownerType', 'agent')
            ->assertSet('contactType', 'messages_only');
    }

    // ---------- Коммерческая недвижимость: шаг 1 мастера ----------

    public function test_commercial_step1_shows_renter_label_for_rent_and_seller_label_for_sale(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommercialPropertyWizard::class)
            ->set('dealType', 'rent')
            ->assertSee('Кто сдаёт')
            ->set('dealType', 'sale')
            ->assertSee('Кто продаёт');
    }

    public function test_commercial_wizard_creates_listing_with_owner_and_calls_and_messages(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommercialPropertyWizard::class)
            ->set('dealType', 'sale')
            ->set('ownerType', 'owner')
            ->set('contactType', 'calls_and_messages')
            ->call('nextStep')
            ->set('address', 'г. Москва, ул. Деловая, д. 1')
            ->set('lat', 55.751244)
            ->set('lng', 37.618423)
            ->call('nextStep')
            ->set('area', 100)
            ->set('floor', 2)
            ->set('totalFloors', 10)
            ->set('description', 'Отличный офис в бизнес-центре.')
            ->call('nextStep')
            ->set('price', 15000000)
            ->call('nextStep')
            ->call('submit')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('commercial_properties', [
            'user_id' => $user->id,
            'owner_type' => 'owner',
            'contact_type' => 'calls_and_messages',
        ]);
    }

    public function test_commercial_step1_rejects_invalid_owner_and_contact_type(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommercialPropertyWizard::class)
            ->set('ownerType', 'invalid')
            ->set('contactType', 'invalid')
            ->call('nextStep')
            ->assertHasErrors(['ownerType', 'contactType'])
            ->assertSet('step', 1);
    }

    public function test_commercial_wizard_edit_prefills_owner_and_contact_type(): void
    {
        $user = User::factory()->create();
        $listing = CommercialProperty::factory()->create([
            'user_id' => $user->id,
            'deal_type' => 'sale',
            'owner_type' => 'agent',
            'contact_type' => 'messages_only',
        ]);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        Livewire::actingAs($user)
            ->test(CommercialPropertyWizard::class, ['commercialProperty' => $listing])
            ->assertSet('ownerType', 'agent')
            ->assertSet('contactType', 'messages_only');
    }

    // ---------- Страница объекта: жилая недвижимость ----------

    public function test_residential_show_displays_seller_for_owner_sale(): void
    {
        $listing = ResidentialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'owner_type' => 'owner',
            'contact_type' => 'calls_and_messages',
        ]);

        $this->get(route('residential.show', $listing))
            ->assertOk()
            ->assertSee('Продавец')
            ->assertDontSee('Агент')
            ->assertSee('Звонки и сообщения');
    }

    public function test_residential_show_displays_landlord_for_owner_rent(): void
    {
        $listing = ResidentialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'rent',
            'owner_type' => 'owner',
            'contact_type' => 'messages_only',
        ]);

        $this->get(route('residential.show', $listing))
            ->assertOk()
            ->assertSee('Арендодатель')
            ->assertDontSee('Продавец')
            ->assertSee('Только сообщения');
    }

    public function test_residential_show_displays_agent_regardless_of_deal_type(): void
    {
        $listing = ResidentialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'owner_type' => 'agent',
        ]);

        $this->get(route('residential.show', $listing))
            ->assertOk()
            ->assertSee('Агент')
            ->assertDontSee('Продавец');
    }

    // ---------- Страница объекта: коммерческая недвижимость ----------

    public function test_commercial_show_displays_seller_for_owner_sale(): void
    {
        $listing = CommercialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'owner_type' => 'owner',
            'contact_type' => 'calls_and_messages',
        ]);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        $this->get(route('commercial.show', $listing))
            ->assertOk()
            ->assertSee('Продавец')
            ->assertDontSee('Агент')
            ->assertSee('Звонки и сообщения');
    }

    public function test_commercial_show_displays_landlord_for_owner_rent(): void
    {
        $listing = CommercialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'rent',
            'owner_type' => 'owner',
            'contact_type' => 'messages_only',
        ]);
        \App\Models\CommercialRentDetail::factory()->create(['property_id' => $listing->id]);

        $this->get(route('commercial.show', $listing))
            ->assertOk()
            ->assertSee('Арендодатель')
            ->assertDontSee('Продавец')
            ->assertSee('Только сообщения');
    }

    public function test_commercial_show_displays_agent_regardless_of_deal_type(): void
    {
        $listing = CommercialProperty::factory()->create([
            'status' => 'active',
            'deal_type' => 'sale',
            'owner_type' => 'agent',
        ]);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        $this->get(route('commercial.show', $listing))
            ->assertOk()
            ->assertSee('Агент')
            ->assertDontSee('Продавец');
    }
}
