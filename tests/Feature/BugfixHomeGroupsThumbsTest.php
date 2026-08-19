<?php

namespace Tests\Feature;

use App\Livewire\CommercialProperty\CreateWizard as CommercialPropertyWizard;
use App\Livewire\Property\CreateWizard as ResidentialPropertyWizard;
use App\Livewire\Workspace\CreateWizard as WorkspaceWizard;
use App\Models\CommercialProperty;
use App\Models\CommercialSaleDetail;
use App\Models\PropertyPhoto;
use App\Models\ResidentialProperty;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspacePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Доработки, запрошенные пользователем после Вехи 3:
 * 1) на главной — три отдельные группы подборок (жильё/коммерция/рабочие
 *    пространства) вместо одной общей, каждая с новыми сверху и своей
 *    ссылкой «Смотреть все»;
 * 2) мини-фото слева от краткой информации в карточках (главная + все три
 *    каталога), при отсутствии фото — карточка как раньше;
 * 3) раздел в ЛК переименован в «Мои объявления (жилая недвижимость)»;
 * 4) на шаге фото в мастере создания объявления — кликабельная область
 *    теперь с иконкой вместо голого input[type=file].
 */
class BugfixHomeGroupsThumbsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_three_separate_groups_newest_first(): void
    {
        $residentialOld = ResidentialProperty::factory()->create(['status' => 'active', 'address' => 'ул. Жилая Старая, 1']);
        $residentialOld->forceFill(['created_at' => now()->subDays(2)])->save();
        $residentialNew = ResidentialProperty::factory()->create(['status' => 'active', 'address' => 'ул. Жилая Новая, 2']);

        $commercial = CommercialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale', 'address' => 'ул. Коммерческая, 3']);
        CommercialSaleDetail::factory()->create(['property_id' => $commercial->id]);

        $workspace = Workspace::factory()->create(['status' => 'active', 'address' => 'ул. Рабочая, 4']);
        WorkspacePricing::factory()->create(['workspace_id' => $workspace->id, 'period' => 'day', 'price' => 2000]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Жилая недвижимость')
            ->assertSee('Коммерческая недвижимость')
            ->assertSee('Рабочие пространства')
            ->assertSee('ул. Жилая Новая, 2')
            ->assertSee('ул. Коммерческая, 3')
            ->assertSee('ул. Рабочая, 4');

        // Новая жилая карточка должна идти раньше старой в HTML.
        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, 'ул. Жилая Старая, 1'),
            strpos($content, 'ул. Жилая Новая, 2')
        );
    }

    public function test_home_group_see_all_links_point_to_correct_catalogs(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('residential.search'), false)
            ->assertSee(route('commercial.search'), false)
            ->assertSee(route('workspace.search'), false);
    }

    public function test_home_card_shows_thumbnail_when_photo_exists(): void
    {
        $listing = ResidentialProperty::factory()->create(['status' => 'active']);
        PropertyPhoto::factory()->create([
            'photoable_id' => $listing->id,
            'photoable_type' => ResidentialProperty::class,
            'path' => 'property-photos/home-thumb-test.webp',
            'is_main' => true,
        ]);

        $this->get('/')->assertSee('property-photos/home-thumb-test.webp', false);
    }

    public function test_catalog_card_has_no_photo_slot_when_listing_has_no_photo(): void
    {
        $listing = ResidentialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale', 'address' => 'ул. Без Фото, 8']);

        $response = $this->get(route('residential.search'));

        $response->assertOk()->assertSee('ул. Без Фото, 8');
        $this->assertStringNotContainsString('property-photos/', $response->getContent());
    }

    public function test_commercial_catalog_card_shows_thumbnail_when_photo_exists(): void
    {
        $listing = CommercialProperty::factory()->create(['status' => 'active', 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);
        PropertyPhoto::factory()->create([
            'photoable_id' => $listing->id,
            'photoable_type' => CommercialProperty::class,
            'path' => 'property-photos/commercial-thumb-test.webp',
            'is_main' => true,
        ]);

        $this->get(route('commercial.search'))->assertSee('property-photos/commercial-thumb-test.webp', false);
    }

    public function test_workspace_catalog_card_shows_thumbnail_when_photo_exists(): void
    {
        $listing = Workspace::factory()->create(['status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);
        PropertyPhoto::factory()->create([
            'photoable_id' => $listing->id,
            'photoable_type' => Workspace::class,
            'path' => 'property-photos/workspace-thumb-test.webp',
            'is_main' => true,
        ]);

        $this->get(route('workspace.search'))->assertSee('property-photos/workspace-thumb-test.webp', false);
    }

    public function test_dashboard_residential_section_has_type_suffix(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertSee('Мои объявления (жилая недвижимость)')
            ->assertSee('Мои объявления (коммерческая недвижимость)')
            ->assertSee('Мои объявления (рабочие пространства)');
    }

    public function test_residential_wizard_photo_step_has_clickable_dropzone_with_icon(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(ResidentialPropertyWizard::class);
        // Шаг 4 стал шагом "Цена" (по аналогии с коммерческой недвижимостью),
        // фотографии переехали на шаг 5.
        $component->set('step', 5);

        $component->assertSee('Нажмите, чтобы выбрать фотографии');
    }

    public function test_commercial_wizard_photo_step_has_clickable_dropzone_with_icon(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(CommercialPropertyWizard::class);
        $component->set('step', 5);

        $component->assertSee('Нажмите, чтобы выбрать фотографии');
    }

    public function test_workspace_wizard_photo_step_has_clickable_dropzone_with_icon(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(WorkspaceWizard::class);
        $component->set('step', 5);

        $component->assertSee('Нажмите, чтобы выбрать фотографии');
    }
}
