<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase0ScaffoldTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_catalog_page_loads_for_guest(): void
    {
        $this->get('/catalog')->assertStatus(200);
    }

    public function test_admin_login_page_loads(): void
    {
        $this->get('/admin/login')->assertStatus(200);
    }

    public function test_admin_panel_redirects_guest_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_user_can_access_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin')->assertStatus(200);
    }

    public function test_regular_user_cannot_access_panel(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_residential_property_moderation_resource_registered(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/residential-properties')->assertStatus(200);
    }
}
