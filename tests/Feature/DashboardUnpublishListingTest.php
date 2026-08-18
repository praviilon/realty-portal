<?php

namespace Tests\Feature;

use App\Models\CommercialProperty;
use App\Models\CommercialSaleDetail;
use App\Models\ResidentialProperty;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspacePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Доработка по просьбе пользователя: раньше снять объявление с публикации
 * мог только админ (через Filament, вручную меняя статус на 'archived').
 * Теперь у владельца объявления есть кнопка "Снять с публикации" прямо в
 * личном кабинете (/dashboard), рядом с "Изменить" — но только пока
 * объявление в статусе 'active'. У объявлений в других статусах
 * (на модерации, отклонено, уже в архиве) кнопка не показывается вовсе —
 * остаётся только "Изменить" (см. App\Http\Controllers\ListingUnpublishController
 * и resources/views/dashboard.blade.php).
 */
class DashboardUnpublishListingTest extends TestCase
{
    use RefreshDatabase;

    // --- Жилая недвижимость ---

    public function test_unpublish_button_shown_only_for_active_residential_listing(): void
    {
        $user = User::factory()->create();
        $active = ResidentialProperty::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $moderation = ResidentialProperty::factory()->moderation()->create(['user_id' => $user->id]);
        $rejected = ResidentialProperty::factory()->create(['user_id' => $user->id, 'status' => 'rejected', 'rejection_reason' => 'test']);
        $archived = ResidentialProperty::factory()->create(['user_id' => $user->id, 'status' => 'archived']);

        $content = $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringContainsString(route('residential.unpublish', $active), $content);
        $this->assertStringNotContainsString(route('residential.unpublish', $moderation), $content);
        $this->assertStringNotContainsString(route('residential.unpublish', $rejected), $content);
        $this->assertStringNotContainsString(route('residential.unpublish', $archived), $content);

        // "Снять с публикации" должно встретиться ровно один раз — только у активного.
        $this->assertSame(1, substr_count($content, 'Снять с публикации'));
    }

    public function test_owner_can_unpublish_own_active_residential_listing(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['user_id' => $user->id, 'status' => 'active']);

        $response = $this->actingAs($user)->post(route('residential.unpublish', $listing));

        $response->assertRedirect();
        $this->assertSame('archived', $listing->fresh()->status);
    }

    public function test_stranger_cannot_unpublish_someone_elses_residential_listing(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['user_id' => $owner->id, 'status' => 'active']);

        $this->actingAs($stranger)
            ->post(route('residential.unpublish', $listing))
            ->assertForbidden();

        $this->assertSame('active', $listing->fresh()->status);
    }

    public function test_cannot_unpublish_residential_listing_that_is_not_active(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->moderation()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('residential.unpublish', $listing))
            ->assertForbidden();

        $this->assertSame('moderation', $listing->fresh()->status);
    }

    public function test_guest_cannot_unpublish_residential_listing(): void
    {
        $listing = ResidentialProperty::factory()->create(['status' => 'active']);

        $this->post(route('residential.unpublish', $listing))
            ->assertRedirect(route('login'));

        $this->assertSame('active', $listing->fresh()->status);
    }

    // --- Коммерческая недвижимость ---

    public function test_unpublish_button_shown_only_for_active_commercial_listing(): void
    {
        $user = User::factory()->create();
        $active = CommercialProperty::factory()->create(['user_id' => $user->id, 'status' => 'active', 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $active->id]);
        $archived = CommercialProperty::factory()->create(['user_id' => $user->id, 'status' => 'archived', 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $archived->id]);

        $content = $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringContainsString(route('commercial.unpublish', $active), $content);
        $this->assertStringNotContainsString(route('commercial.unpublish', $archived), $content);
    }

    public function test_owner_can_unpublish_own_active_commercial_listing(): void
    {
        $user = User::factory()->create();
        $listing = CommercialProperty::factory()->create(['user_id' => $user->id, 'status' => 'active', 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        $response = $this->actingAs($user)->post(route('commercial.unpublish', $listing));

        $response->assertRedirect();
        $this->assertSame('archived', $listing->fresh()->status);
    }

    public function test_stranger_cannot_unpublish_someone_elses_commercial_listing(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $listing = CommercialProperty::factory()->create(['user_id' => $owner->id, 'status' => 'active', 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        $this->actingAs($stranger)
            ->post(route('commercial.unpublish', $listing))
            ->assertForbidden();

        $this->assertSame('active', $listing->fresh()->status);
    }

    public function test_cannot_unpublish_commercial_listing_that_is_not_active(): void
    {
        $user = User::factory()->create();
        $listing = CommercialProperty::factory()->create(['user_id' => $user->id, 'status' => 'rejected', 'rejection_reason' => 'test', 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        $this->actingAs($user)
            ->post(route('commercial.unpublish', $listing))
            ->assertForbidden();

        $this->assertSame('rejected', $listing->fresh()->status);
    }

    // --- Рабочие пространства ---

    public function test_unpublish_button_shown_only_for_active_workspace_listing(): void
    {
        $user = User::factory()->create();
        $active = Workspace::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $active->id, 'period' => 'day', 'price' => 2000]);
        $archived = Workspace::factory()->create(['user_id' => $user->id, 'status' => 'archived']);
        WorkspacePricing::factory()->create(['workspace_id' => $archived->id, 'period' => 'day', 'price' => 2000]);

        $content = $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringContainsString(route('workspace.unpublish', $active), $content);
        $this->assertStringNotContainsString(route('workspace.unpublish', $archived), $content);
    }

    public function test_owner_can_unpublish_own_active_workspace_listing(): void
    {
        $user = User::factory()->create();
        $listing = Workspace::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $response = $this->actingAs($user)->post(route('workspace.unpublish', $listing));

        $response->assertRedirect();
        $this->assertSame('archived', $listing->fresh()->status);
    }

    public function test_stranger_cannot_unpublish_someone_elses_workspace_listing(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $listing = Workspace::factory()->create(['user_id' => $owner->id, 'status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $this->actingAs($stranger)
            ->post(route('workspace.unpublish', $listing))
            ->assertForbidden();

        $this->assertSame('active', $listing->fresh()->status);
    }

    public function test_cannot_unpublish_workspace_listing_that_is_not_active(): void
    {
        $user = User::factory()->create();
        $listing = Workspace::factory()->create(['user_id' => $user->id, 'status' => 'archived']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $this->actingAs($user)
            ->post(route('workspace.unpublish', $listing))
            ->assertForbidden();

        $this->assertSame('archived', $listing->fresh()->status);
    }

    // --- Общее поведение ---

    public function test_unpublishing_redirects_back_to_dashboard_with_status_message(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['user_id' => $user->id, 'status' => 'active']);

        $response = $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('residential.unpublish', $listing));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status', 'Объявление снято с публикации и перемещено в архив.');
    }

    public function test_archived_listing_status_badge_and_no_unpublish_button_after_action(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['user_id' => $user->id, 'status' => 'active']);

        $this->actingAs($user)->post(route('residential.unpublish', $listing));

        $content = $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringContainsString('В архиве', $content);
        $this->assertStringNotContainsString(route('residential.unpublish', $listing), $content);
    }
}
