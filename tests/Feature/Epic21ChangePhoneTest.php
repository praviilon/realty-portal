<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Эпик 21 (Веха 2): ЛК — смена телефона. Механика уже существует как часть
 * общей формы «Данные профиля» (эпик 9, Веха 1, Livewire Volt-компонент
 * profile.update-profile-information-form) — это осознанное упрощение
 * (см. паттерн раздела 5 технического плана: не заводить отдельный экран
 * там, где хватает уже существующего). Тесты ниже закрывают именно сценарий
 * "смена телефона" (было значение -> новое значение), который раньше не был
 * явно покрыт (были тесты на ввод телефона с нуля и на его очистку).
 */
class Epic21ChangePhoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('profile'))->assertRedirect(route('login'));
    }

    public function test_user_can_change_existing_phone_to_a_new_one(): void
    {
        $user = User::factory()->create(['phone' => '+7 (902) 111-22-33']);

        Livewire::actingAs($user)
            ->test('profile.update-profile-information-form')
            ->set('phone', '+7 (903) 444-55-66')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertSame('+7 (903) 444-55-66', $user->refresh()->phone);
    }

    public function test_changing_phone_to_a_number_already_used_by_another_user_is_rejected(): void
    {
        User::factory()->create(['phone' => '+7 (903) 444-55-66']);
        $user = User::factory()->create(['phone' => '+7 (902) 111-22-33']);

        Livewire::actingAs($user)
            ->test('profile.update-profile-information-form')
            ->set('phone', '+7 (903) 444-55-66')
            ->call('updateProfileInformation')
            ->assertHasErrors(['phone']);

        $this->assertSame('+7 (902) 111-22-33', $user->refresh()->phone);
    }

    public function test_changing_phone_to_an_invalid_format_is_rejected(): void
    {
        $user = User::factory()->create(['phone' => '+7 (902) 111-22-33']);

        Livewire::actingAs($user)
            ->test('profile.update-profile-information-form')
            ->set('phone', '89021112233')
            ->call('updateProfileInformation')
            ->assertHasErrors(['phone']);

        $this->assertSame('+7 (902) 111-22-33', $user->refresh()->phone);
    }

    public function test_user_can_keep_their_own_phone_unchanged(): void
    {
        // Уникальность игнорирует самого пользователя (Rule::unique()->ignore($user->id)) —
        // повторное сохранение формы с тем же номером не должно давать ошибку валидации.
        $user = User::factory()->create(['phone' => '+7 (902) 111-22-33']);

        Livewire::actingAs($user)
            ->test('profile.update-profile-information-form')
            ->set('phone', '+7 (902) 111-22-33')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertSame('+7 (902) 111-22-33', $user->refresh()->phone);
    }

    public function test_profile_page_is_reachable_via_real_route_and_shows_phone_field(): void
    {
        $user = User::factory()->create(['phone' => '+7 (902) 111-22-33']);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Телефон')
            ->assertSee('+7 (902) 111-22-33', false);
    }
}
