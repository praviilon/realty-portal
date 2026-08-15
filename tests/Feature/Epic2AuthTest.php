<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class Epic2AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_loads(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_user_can_register_with_valid_data_and_name_gets_capitalized(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('first_name', 'иван')
            ->set('last_name', 'петров-сидоров')
            ->set('email', 'ivan@example.com')
            ->set('phone', '+7 (901) 234-56-78')
            ->set('password', 'Passw0rd')
            ->set('password_confirmation', 'Passw0rd')
            ->call('register');

        $component->assertHasNoErrors();

        $this->assertAuthenticated();

        $user = User::where('email', 'ivan@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('Иван', $user->first_name);
        $this->assertSame('Петров-Сидоров', $user->last_name);
        $this->assertSame('user', $user->role);
        $this->assertSame('+7 (901) 234-56-78', $user->phone);
    }

    public function test_registration_rejects_latin_name(): void
    {
        Volt::test('pages.auth.register')
            ->set('first_name', 'Ivan')
            ->set('last_name', '')
            ->set('email', 'ivan2@example.com')
            ->set('phone', '+7 (901) 234-56-79')
            ->set('password', 'Passw0rd')
            ->set('password_confirmation', 'Passw0rd')
            ->call('register')
            ->assertHasErrors(['first_name']);
    }

    public function test_registration_rejects_bad_phone_format(): void
    {
        Volt::test('pages.auth.register')
            ->set('first_name', 'Иван')
            ->set('email', 'ivan3@example.com')
            ->set('phone', '89012345678')
            ->set('password', 'Passw0rd')
            ->set('password_confirmation', 'Passw0rd')
            ->call('register')
            ->assertHasErrors(['phone']);
    }

    public function test_registration_rejects_weak_password(): void
    {
        Volt::test('pages.auth.register')
            ->set('first_name', 'Иван')
            ->set('email', 'ivan4@example.com')
            ->set('phone', '+7 (901) 234-56-80')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasErrors(['password']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'login-test@example.com',
            'password' => bcrypt('Passw0rd'),
        ]);

        Volt::test('pages.auth.login')
            ->set('form.email', 'login-test@example.com')
            ->set('form.password', 'Passw0rd')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_dashboard_accessible_without_email_verification(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertStatus(200);
    }
}
