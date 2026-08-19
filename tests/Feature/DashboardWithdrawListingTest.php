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
 * Доработка по повторной просьбе пользователя: у объявления в статусе
 * 'moderation' в личном кабинете (/dashboard) рядом с "Изменить" появляется
 * кнопка "Отозвать" — тот же принцип, что и у "Снять с публикации" для
 * активных объявлений (см. DashboardUnpublishListingTest), но переход
 * 'moderation' -> 'archived' вместо 'active' -> 'archived'. У объявлений в
 * любом другом статусе (активно, отклонено, уже в архиве) кнопка "Отозвать"
 * не показывается — остаётся только "Изменить" (см.
 * App\Http\Controllers\ListingWithdrawController и resources/views/dashboard.blade.php).
 */
class DashboardWithdrawListingTest extends TestCase
{
    use RefreshDatabase;

    // --- Жилая недвижимость ---

    public function test_withdraw_button_shown_only_for_moderation_residential_listing(): void
    {
        $user = User::factory()->create();
        $moderation = ResidentialProperty::factory()->moderation()->create(['user_id' => $user->id]);
        $active = ResidentialProperty::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $rejected = ResidentialProperty::factory()->create(['user_id' => $user->id, 'status' => 'rejected', 'rejection_reason' => 'test']);
        $archived = ResidentialProperty::factory()->create(['user_id' => $user->id, 'status' => 'archived']);

        $content = $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringContainsString(route('residential.withdraw', $moderation), $content);
        $this->assertStringNotContainsString(route('residential.withdraw', $active), $content);
        $this->assertStringNotContainsString(route('residential.withdraw', $rejected), $content);
        $this->assertStringNotContainsString(route('residential.withdraw', $archived), $content);

        // Кнопка "Отозвать" (сам видимый текст кнопки, а не текст в confirm()
        // JS-диалоге формы, где слово "Отозвать" тоже встречается) должна
        // быть ровно одна — только у объявления на модерации.
        $this->assertSame(1, substr_count($content, '>Отозвать</button>'));

        // У активного объявления по-прежнему должна быть кнопка "Снять с публикации", а не "Отозвать".
        $this->assertStringContainsString(route('residential.unpublish', $active), $content);
    }

    public function test_owner_can_withdraw_own_moderation_residential_listing(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->moderation()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('residential.withdraw', $listing));

        $response->assertRedirect();
        $this->assertSame('archived', $listing->fresh()->status);
    }

    public function test_stranger_cannot_withdraw_someone_elses_residential_listing(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $listing = ResidentialProperty::factory()->moderation()->create(['user_id' => $owner->id]);

        $this->actingAs($stranger)
            ->post(route('residential.withdraw', $listing))
            ->assertForbidden();

        $this->assertSame('moderation', $listing->fresh()->status);
    }

    public function test_cannot_withdraw_residential_listing_that_is_not_in_moderation(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['user_id' => $user->id, 'status' => 'active']);

        $this->actingAs($user)
            ->post(route('residential.withdraw', $listing))
            ->assertForbidden();

        $this->assertSame('active', $listing->fresh()->status);
    }

    public function test_guest_cannot_withdraw_residential_listing(): void
    {
        $listing = ResidentialProperty::factory()->moderation()->create();

        $this->post(route('residential.withdraw', $listing))
            ->assertRedirect(route('login'));

        $this->assertSame('moderation', $listing->fresh()->status);
    }

    // --- Коммерческая недвижимость ---

    public function test_withdraw_button_shown_only_for_moderation_commercial_listing(): void
    {
        $user = User::factory()->create();
        $moderation = CommercialProperty::factory()->create(['user_id' => $user->id, 'status' => 'moderation', 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $moderation->id]);
        $active = CommercialProperty::factory()->create(['user_id' => $user->id, 'status' => 'active', 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $active->id]);

        $content = $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringContainsString(route('commercial.withdraw', $moderation), $content);
        $this->assertStringNotContainsString(route('commercial.withdraw', $active), $content);
    }

    public function test_owner_can_withdraw_own_moderation_commercial_listing(): void
    {
        $user = User::factory()->create();
        $listing = CommercialProperty::factory()->create(['user_id' => $user->id, 'status' => 'moderation', 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        $response = $this->actingAs($user)->post(route('commercial.withdraw', $listing));

        $response->assertRedirect();
        $this->assertSame('archived', $listing->fresh()->status);
    }

    public function test_stranger_cannot_withdraw_someone_elses_commercial_listing(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $listing = CommercialProperty::factory()->create(['user_id' => $owner->id, 'status' => 'moderation', 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        $this->actingAs($stranger)
            ->post(route('commercial.withdraw', $listing))
            ->assertForbidden();

        $this->assertSame('moderation', $listing->fresh()->status);
    }

    public function test_cannot_withdraw_commercial_listing_that_is_not_in_moderation(): void
    {
        $user = User::factory()->create();
        $listing = CommercialProperty::factory()->create(['user_id' => $user->id, 'status' => 'archived', 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);

        $this->actingAs($user)
            ->post(route('commercial.withdraw', $listing))
            ->assertForbidden();

        $this->assertSame('archived', $listing->fresh()->status);
    }

    // --- Рабочие пространства ---

    public function test_withdraw_button_shown_only_for_moderation_workspace_listing(): void
    {
        $user = User::factory()->create();
        $moderation = Workspace::factory()->create(['user_id' => $user->id, 'status' => 'moderation']);
        WorkspacePricing::factory()->create(['workspace_id' => $moderation->id, 'period' => 'day', 'price' => 2000]);
        $active = Workspace::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $active->id, 'period' => 'day', 'price' => 2000]);

        $content = $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringContainsString(route('workspace.withdraw', $moderation), $content);
        $this->assertStringNotContainsString(route('workspace.withdraw', $active), $content);
    }

    public function test_owner_can_withdraw_own_moderation_workspace_listing(): void
    {
        $user = User::factory()->create();
        $listing = Workspace::factory()->create(['user_id' => $user->id, 'status' => 'moderation']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $response = $this->actingAs($user)->post(route('workspace.withdraw', $listing));

        $response->assertRedirect();
        $this->assertSame('archived', $listing->fresh()->status);
    }

    public function test_stranger_cannot_withdraw_someone_elses_workspace_listing(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $listing = Workspace::factory()->create(['user_id' => $owner->id, 'status' => 'moderation']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $this->actingAs($stranger)
            ->post(route('workspace.withdraw', $listing))
            ->assertForbidden();

        $this->assertSame('moderation', $listing->fresh()->status);
    }

    public function test_cannot_withdraw_workspace_listing_that_is_not_in_moderation(): void
    {
        $user = User::factory()->create();
        $listing = Workspace::factory()->create(['user_id' => $user->id, 'status' => 'rejected', 'rejection_reason' => 'test']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $this->actingAs($user)
            ->post(route('workspace.withdraw', $listing))
            ->assertForbidden();

        $this->assertSame('rejected', $listing->fresh()->status);
    }

    // --- Общее поведение ---

    public function test_withdrawing_redirects_back_to_dashboard_with_status_message(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->moderation()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('residential.withdraw', $listing));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status', 'Объявление отозвано с модерации и перемещено в архив.');
    }

    public function test_archived_listing_status_badge_and_no_withdraw_button_after_action(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->moderation()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('residential.withdraw', $listing));

        $content = $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringContainsString('В архиве', $content);
        $this->assertStringNotContainsString(route('residential.withdraw', $listing), $content);
    }
}
