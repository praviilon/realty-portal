<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Эпик 22 (Веха 2): ЛК — смена пароля. Механика — стандартный
 * Livewire Volt-компонент из Breeze (profile.update-password-form),
 * который до этого эпика был на английском и проверял пароль через
 * встроенный Password::defaults() вместо App\Rules\PasswordPolicy,
 * используемого при регистрации (эпик 2). Приведён в соответствие:
 * локализован и теперь применяет ту же политику пароля.
 */
class Epic22ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPass1')]);

        Livewire::actingAs($user)
            ->test('profile.update-password-form')
            ->set('current_password', 'OldPass1')
            ->set('password', 'NewPass2')
            ->set('password_confirmation', 'NewPass2')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('NewPass2', $user->refresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPass1')]);

        Livewire::actingAs($user)
            ->test('profile.update-password-form')
            ->set('current_password', 'WrongPass1')
            ->set('password', 'NewPass2')
            ->set('password_confirmation', 'NewPass2')
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);

        $this->assertTrue(Hash::check('OldPass1', $user->refresh()->password));
    }

    public function test_new_password_must_match_confirmation(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPass1')]);

        Livewire::actingAs($user)
            ->test('profile.update-password-form')
            ->set('current_password', 'OldPass1')
            ->set('password', 'NewPass2')
            ->set('password_confirmation', 'DoesNotMatch2')
            ->call('updatePassword')
            ->assertHasErrors(['password']);

        $this->assertTrue(Hash::check('OldPass1', $user->refresh()->password));
    }

    public function test_new_password_must_satisfy_password_policy(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPass1')]);

        Livewire::actingAs($user)
            ->test('profile.update-password-form')
            ->set('current_password', 'OldPass1')
            ->set('password', 'short')
            ->set('password_confirmation', 'short')
            ->call('updatePassword')
            ->assertHasErrors(['password']);

        $this->assertTrue(Hash::check('OldPass1', $user->refresh()->password));
    }

    public function test_user_can_log_in_with_new_password_after_change(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPass1')]);

        Livewire::actingAs($user)
            ->test('profile.update-password-form')
            ->set('current_password', 'OldPass1')
            ->set('password', 'NewPass2')
            ->set('password_confirmation', 'NewPass2')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->post('/logout');

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'NewPass2')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_profile_page_shows_localized_password_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertSee('Смена пароля')
            ->assertSee('Текущий пароль')
            ->assertDontSee('Update Password')
            ->assertDontSee('Current Password');
    }
}
