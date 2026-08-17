<?php

namespace Tests\Feature;

use App\Models\PropertyPhoto;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspacePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Epic27WorkspaceShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_displays_full_listing_details(): void
    {
        $owner = User::factory()->create(['first_name' => 'Анна', 'last_name' => 'Смирнова']);
        $listing = Workspace::factory()->create([
            'status' => 'active',
            'user_id' => $owner->id,
            'address' => 'ул. Коворкинг, 15',
            'area' => 45,
            'description' => 'Уютное рабочее пространство в центре.',
        ]);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2500]);

        $visitor = User::factory()->create();

        $response = $this->actingAs($visitor)->get(route('workspace.show', $listing));

        $response->assertStatus(200)
            ->assertSee('ул. Коворкинг, 15')
            ->assertSee('2 500')
            ->assertSee('45')
            ->assertSee('Анна Смирнова')
            ->assertSee('Написать');
    }

    public function test_show_page_displays_the_cheapest_price_from_multiple_pricing_rows(): void
    {
        $listing = Workspace::factory()->create(['status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'hour', 'price' => 700]);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'month', 'price' => 30000]);

        $this->get(route('workspace.show', $listing))
            ->assertSee('от 700')
            ->assertSee('700')
            ->assertSee('30 000');
    }

    public function test_show_page_displays_photos_when_present(): void
    {
        $listing = Workspace::factory()->create(['status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);
        PropertyPhoto::factory()->create([
            'photoable_id' => $listing->id,
            'photoable_type' => Workspace::class,
            'path' => 'property-photos/workspace-test-photo.webp',
        ]);

        $this->get(route('workspace.show', $listing))
            ->assertSee('property-photos/workspace-test-photo.webp', false);
    }

    public function test_show_page_displays_amenities_and_access_time(): void
    {
        $listing = Workspace::factory()->create([
            'status' => 'active',
            'amenities' => ['wifi', 'coffee'],
            'access_time' => [['type' => 'round_the_clock']],
        ]);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $this->get(route('workspace.show', $listing))
            ->assertSee('Wi-Fi')
            ->assertSee('Кофе/чай')
            ->assertSee('Круглосуточно');
    }

    public function test_views_count_increments_once_per_session(): void
    {
        $listing = Workspace::factory()->create(['status' => 'active', 'views_count' => 0]);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $this->get(route('workspace.show', $listing));
        $this->get(route('workspace.show', $listing));

        $this->assertSame(1, $listing->fresh()->views_count);
    }

    public function test_moderation_listing_hidden_from_strangers_but_visible_to_owner(): void
    {
        $owner = User::factory()->create();
        $listing = Workspace::factory()->moderation()->create(['user_id' => $owner->id]);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('workspace.show', $listing))->assertNotFound();
        $this->actingAs($owner)->get(route('workspace.show', $listing))->assertOk();
    }

    public function test_map_is_rendered_on_show_page(): void
    {
        $listing = Workspace::factory()->create(['status' => 'active']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 2000]);

        $this->get(route('workspace.show', $listing))
            ->assertSee('Расположение на карте');
    }

    public function test_catalog_to_show_page_navigation_flow(): void
    {
        $listing = Workspace::factory()->create(['status' => 'active', 'address' => 'ул. Переходная, 4']);
        WorkspacePricing::factory()->create(['workspace_id' => $listing->id, 'period' => 'day', 'price' => 4000]);

        $this->get(route('workspace.search'))
            ->assertOk()
            ->assertSee($listing->address)
            ->assertSee(route('workspace.show', $listing), false);
    }
}
