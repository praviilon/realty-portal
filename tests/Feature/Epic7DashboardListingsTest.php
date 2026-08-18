<?php

namespace Tests\Feature;

use App\Models\CommercialProperty;
use App\Models\CommercialSaleDetail;
use App\Models\PropertyPhoto;
use App\Models\ResidentialProperty;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspacePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Epic7DashboardListingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_own_listings_with_status_and_edit_link(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->moderation()->create([
            'user_id' => $user->id,
            'address' => 'ул. Личный кабинет, 1',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('ул. Личный кабинет, 1')
            ->assertSee('На модерации')
            ->assertSee(route('residential.edit', $listing), false)
            ->assertSee(route('residential.create'), false);
    }

    public function test_dashboard_shows_empty_state_without_listings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('У вас пока нет объявлений');
    }

    /**
     * Доработка по просьбе пользователя: мини-карточка с фото слева от
     * каждого объявления в ЛК — по аналогии с x-listing-thumb, который уже
     * используется на главной, в каталогах и в Избранном (см.
     * BugfixHomeGroupsThumbsTest). Если фото нет — компонент ничего не
     * рендерит, карточка выглядит как раньше.
     */
    public function test_dashboard_residential_listing_shows_thumbnail_when_photo_exists(): void
    {
        $user = User::factory()->create();
        $listing = ResidentialProperty::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        PropertyPhoto::factory()->create([
            'photoable_id' => $listing->id,
            'photoable_type' => ResidentialProperty::class,
            'path' => 'property-photos/dashboard-residential-thumb-test.webp',
            'is_main' => true,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertSee('property-photos/dashboard-residential-thumb-test.webp', false);
    }

    public function test_dashboard_residential_listing_has_no_photo_slot_when_no_photo(): void
    {
        $user = User::factory()->create();
        ResidentialProperty::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'address' => 'ул. Без Фото В Кабинете, 1',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()->assertSee('ул. Без Фото В Кабинете, 1');
        $this->assertStringNotContainsString('property-photos/', $response->getContent());
    }

    public function test_dashboard_commercial_listing_shows_thumbnail_when_photo_exists(): void
    {
        $user = User::factory()->create();
        $listing = CommercialProperty::factory()->create(['user_id' => $user->id, 'status' => 'active', 'deal_type' => 'sale']);
        CommercialSaleDetail::factory()->create(['property_id' => $listing->id]);
        PropertyPhoto::factory()->create([
            'photoable_id' => $listing->id,
            'photoable_type' => CommercialProperty::class,
            'path' => 'property-photos/dashboard-commercial-thumb-test.webp',
            'is_main' => true,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertSee('property-photos/dashboard-commercial-thumb-test.webp', false);
    }

    public function test_dashboard_workspace_listing_shows_thumbnail_when_photo_exists(): void
    {
        $user = User::factory()->create();
        $listing = Workspace::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);
        PropertyPhoto::factory()->create([
            'photoable_id' => $listing->id,
            'photoable_type' => Workspace::class,
            'path' => 'property-photos/dashboard-workspace-thumb-test.webp',
            'is_main' => true,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertSee('property-photos/dashboard-workspace-thumb-test.webp', false);
    }

    public function test_dashboard_does_not_leak_thumbnail_between_different_users_listings(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $strangerListing = ResidentialProperty::factory()->create(['user_id' => $stranger->id, 'status' => 'active']);
        PropertyPhoto::factory()->create([
            'photoable_id' => $strangerListing->id,
            'photoable_type' => ResidentialProperty::class,
            'path' => 'property-photos/stranger-thumb-test.webp',
            'is_main' => true,
        ]);

        $this->actingAs($owner)
            ->get('/dashboard')
            ->assertDontSee('property-photos/stranger-thumb-test.webp', false);
    }
}
