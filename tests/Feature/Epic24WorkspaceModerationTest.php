<?php

namespace Tests\Feature;

use App\Filament\Resources\WorkspaceResource\Pages\ListWorkspaces;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Epic24WorkspaceModerationTest extends TestCase
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
        $listing = Workspace::factory()->moderation()->create(['address' => 'ул. На модерации, 1']);
        Workspace::factory()->create(['status' => 'active', 'address' => 'ул. Активная, 2']);

        Livewire::actingAs($this->admin)
            ->test(ListWorkspaces::class)
            ->assertCanSeeTableRecords([$listing]);
    }

    public function test_admin_can_approve_listing(): void
    {
        $listing = Workspace::factory()->moderation()->create();

        Livewire::actingAs($this->admin)
            ->test(ListWorkspaces::class)
            ->callTableAction('approve', $listing);

        $this->assertSame('active', $listing->fresh()->status);
    }

    public function test_admin_can_reject_listing_with_reason(): void
    {
        $listing = Workspace::factory()->moderation()->create();

        Livewire::actingAs($this->admin)
            ->test(ListWorkspaces::class)
            ->callTableAction('reject', $listing, data: [
                'rejection_reason' => 'Фотографии не соответствуют объекту.',
            ]);

        $listing->refresh();
        $this->assertSame('rejected', $listing->status);
        $this->assertSame('Фотографии не соответствуют объекту.', $listing->rejection_reason);
    }

    public function test_reject_requires_a_reason(): void
    {
        $listing = Workspace::factory()->moderation()->create();

        Livewire::actingAs($this->admin)
            ->test(ListWorkspaces::class)
            ->callTableAction('reject', $listing, data: [
                'rejection_reason' => '',
            ])
            ->assertHasTableActionErrors(['rejection_reason' => 'required']);

        $this->assertSame('moderation', $listing->fresh()->status);
    }

    public function test_approve_and_reject_actions_hidden_for_already_active_listing(): void
    {
        $listing = Workspace::factory()->create(['status' => 'active']);

        Livewire::actingAs($this->admin)
            ->test(ListWorkspaces::class)
            ->assertTableActionHidden('approve', $listing)
            ->assertTableActionHidden('reject', $listing);
    }

    public function test_regular_user_cannot_reach_moderation_panel(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/workspaces')->assertForbidden();
    }

    /**
     * Тот же регресс-тест, что и для жилой/коммерческой недвижимости — бьёт по
     * настоящему HTTP-маршруту, а не по классу Livewire-компонента напрямую,
     * чтобы ловить рассинхрон между расположением Resource-класса и путями
     * discoverResources().
     */
    public function test_admin_can_see_pending_listing_via_real_admin_route(): void
    {
        $listing = Workspace::factory()->moderation()->create(['address' => 'ул. Рабочий Маршрут, 9']);

        $this->actingAs($this->admin)
            ->get('/admin/workspaces')
            ->assertOk()
            ->assertSee('ул. Рабочий Маршрут, 9');
    }
}
