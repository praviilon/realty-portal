<?php

namespace Tests\Feature;

use App\Filament\Resources\ResidentialPropertyResource\Pages\ListResidentialProperties;
use App\Models\ResidentialProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Epic8ModerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_moderation_queue_lists_pending_listings(): void
    {
        $listing = ResidentialProperty::factory()->moderation()->create(['address' => 'ул. На модерации, 1']);
        ResidentialProperty::factory()->create(['status' => 'active', 'address' => 'ул. Активная, 2']);

        Livewire::actingAs($this->admin)
            ->test(ListResidentialProperties::class)
            ->assertCanSeeTableRecords([$listing]);
    }

    public function test_admin_can_approve_listing(): void
    {
        $listing = ResidentialProperty::factory()->moderation()->create();

        Livewire::actingAs($this->admin)
            ->test(ListResidentialProperties::class)
            ->callTableAction('approve', $listing);

        $this->assertSame('active', $listing->fresh()->status);
    }

    public function test_admin_can_reject_listing_with_reason(): void
    {
        $listing = ResidentialProperty::factory()->moderation()->create();

        Livewire::actingAs($this->admin)
            ->test(ListResidentialProperties::class)
            ->callTableAction('reject', $listing, data: [
                'rejection_reason' => 'Фотографии не соответствуют объекту.',
            ]);

        $listing->refresh();
        $this->assertSame('rejected', $listing->status);
        $this->assertSame('Фотографии не соответствуют объекту.', $listing->rejection_reason);
    }

    public function test_reject_requires_a_reason(): void
    {
        $listing = ResidentialProperty::factory()->moderation()->create();

        Livewire::actingAs($this->admin)
            ->test(ListResidentialProperties::class)
            ->callTableAction('reject', $listing, data: [
                'rejection_reason' => '',
            ])
            ->assertHasTableActionErrors(['rejection_reason' => 'required']);

        $this->assertSame('moderation', $listing->fresh()->status);
    }

    public function test_approve_and_reject_actions_hidden_for_already_active_listing(): void
    {
        $listing = ResidentialProperty::factory()->create(['status' => 'active']);

        Livewire::actingAs($this->admin)
            ->test(ListResidentialProperties::class)
            ->assertTableActionHidden('approve', $listing)
            ->assertTableActionHidden('reject', $listing);
    }

    public function test_regular_user_cannot_reach_moderation_panel(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/residential-properties')->assertForbidden();
    }

    /**
     * Регресс-тест: резолвится через настоящий HTTP-маршрут панели (а не через
     * Livewire::test() напрямую по классу компонента), чтобы ловить рассинхрон между
     * расположением класса ResidentialPropertyResource и путями ->discoverResources()
     * в AdminPanelProvider — именно так однажды объявления «на модерации» пропали
     * из реальной админки, хотя все тесты выше (через прямой вызов Livewire-класса)
     * оставались зелёными.
     */
    public function test_admin_can_see_pending_listing_via_real_admin_route(): void
    {
        $listing = ResidentialProperty::factory()->moderation()->create(['address' => 'ул. Реальный Маршрут, 5']);

        $this->actingAs($this->admin)
            ->get('/admin/residential-properties')
            ->assertOk()
            ->assertSee('ул. Реальный Маршрут, 5');
    }
}
