<?php

namespace Tests\Feature;

use App\Filament\Resources\CommercialPropertyResource\Pages\ListCommercialProperties;
use App\Models\CommercialProperty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Epic14CommercialModerationTest extends TestCase
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
        $listing = CommercialProperty::factory()->moderation()->create(['address' => 'ул. На модерации, 1']);
        CommercialProperty::factory()->create(['status' => 'active', 'address' => 'ул. Активная, 2']);

        Livewire::actingAs($this->admin)
            ->test(ListCommercialProperties::class)
            ->assertCanSeeTableRecords([$listing]);
    }

    public function test_admin_can_approve_listing(): void
    {
        $listing = CommercialProperty::factory()->moderation()->create();

        Livewire::actingAs($this->admin)
            ->test(ListCommercialProperties::class)
            ->callTableAction('approve', $listing);

        $this->assertSame('active', $listing->fresh()->status);
    }

    public function test_admin_can_reject_listing_with_reason(): void
    {
        $listing = CommercialProperty::factory()->moderation()->create();

        Livewire::actingAs($this->admin)
            ->test(ListCommercialProperties::class)
            ->callTableAction('reject', $listing, data: [
                'rejection_reason' => 'Фотографии не соответствуют объекту.',
            ]);

        $listing->refresh();
        $this->assertSame('rejected', $listing->status);
        $this->assertSame('Фотографии не соответствуют объекту.', $listing->rejection_reason);
    }

    public function test_reject_requires_a_reason(): void
    {
        $listing = CommercialProperty::factory()->moderation()->create();

        Livewire::actingAs($this->admin)
            ->test(ListCommercialProperties::class)
            ->callTableAction('reject', $listing, data: [
                'rejection_reason' => '',
            ])
            ->assertHasTableActionErrors(['rejection_reason' => 'required']);

        $this->assertSame('moderation', $listing->fresh()->status);
    }

    public function test_approve_and_reject_actions_hidden_for_already_active_listing(): void
    {
        $listing = CommercialProperty::factory()->create(['status' => 'active']);

        Livewire::actingAs($this->admin)
            ->test(ListCommercialProperties::class)
            ->assertTableActionHidden('approve', $listing)
            ->assertTableActionHidden('reject', $listing);
    }

    public function test_regular_user_cannot_reach_moderation_panel(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/commercial-properties')->assertForbidden();
    }

    /**
     * Тот же регресс-тест, что и для жилой недвижимости (Epic8ModerationTest) — бьёт
     * по настоящему HTTP-маршруту, а не по классу Livewire-компонента напрямую, чтобы
     * ловить рассинхрон между расположением Resource-класса и путями discoverResources().
     */
    public function test_admin_can_see_pending_listing_via_real_admin_route(): void
    {
        $listing = CommercialProperty::factory()->moderation()->create(['address' => 'ул. Коммерческий Маршрут, 7']);

        $this->actingAs($this->admin)
            ->get('/admin/commercial-properties')
            ->assertOk()
            ->assertSee('ул. Коммерческий Маршрут, 7');
    }
}
