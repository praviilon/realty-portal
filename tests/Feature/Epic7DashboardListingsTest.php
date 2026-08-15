<?php

namespace Tests\Feature;

use App\Models\ResidentialProperty;
use App\Models\User;
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
}
