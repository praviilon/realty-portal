<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SmartCaptchaVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Эпик 31 (Веха 3) — анти-флуд: SmartCaptcha после 3 неверных попыток пароля
 * на форме входа (App\Livewire\Forms\LoginForm).
 */
class Epic31SmartCaptchaTest extends TestCase
{
    use RefreshDatabase;

    protected function attemptWrongPassword(User $user, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            Volt::test('pages.auth.login')
                ->set('form.email', $user->email)
                ->set('form.password', 'wrong-password')
                ->call('login');
        }
    }

    public function test_captcha_not_required_before_three_failed_attempts(): void
    {
        $user = User::factory()->create();
        $this->attemptWrongPassword($user, 2);

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_captcha_required_after_three_failed_attempts(): void
    {
        $user = User::factory()->create();
        $this->attemptWrongPassword($user, 3);

        // Верный пароль, но без токена капчи — вход должен быть заблокирован.
        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors(['form.captchaToken']);

        $this->assertGuest();
    }

    public function test_login_page_shows_captcha_widget_after_three_failed_attempts(): void
    {
        $user = User::factory()->create();
        $this->attemptWrongPassword($user, 3);

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->assertSee('капч', false);
    }

    public function test_login_succeeds_with_captcha_token_when_server_key_unconfigured(): void
    {
        config(['services.smartcaptcha.server_key' => null]);

        $user = User::factory()->create();
        $this->attemptWrongPassword($user, 3);

        // Ключ SMARTCAPTCHA_SERVER_KEY не задан в этом окружении (как и
        // YANDEX_MAPS_API_KEY) — по тому же принципу, что и карта, любой
        // непустой токен считается пройденной проверкой.
        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->set('form.captchaToken', 'dev-placeholder-token')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_smartcaptcha_verifier_calls_yandex_api_when_server_key_configured(): void
    {
        config(['services.smartcaptcha.server_key' => 'test-server-key']);

        Http::fake([
            'smartcaptcha.yandexcloud.net/*' => Http::response(['status' => 'ok'], 200),
        ]);

        $verifier = app(SmartCaptchaVerifier::class);

        $this->assertTrue($verifier->verify('some-token'));

        Http::assertSent(fn ($request) => $request->url() === 'https://smartcaptcha.yandexcloud.net/validate'
            && $request['secret'] === 'test-server-key'
            && $request['token'] === 'some-token');
    }

    public function test_smartcaptcha_verifier_rejects_failed_yandex_response(): void
    {
        config(['services.smartcaptcha.server_key' => 'test-server-key']);

        Http::fake([
            'smartcaptcha.yandexcloud.net/*' => Http::response(['status' => 'failed'], 200),
        ]);

        $this->assertFalse(app(SmartCaptchaVerifier::class)->verify('bad-token'));
    }

    public function test_smartcaptcha_verifier_rejects_empty_token_even_when_key_configured(): void
    {
        config(['services.smartcaptcha.server_key' => 'test-server-key']);

        $this->assertFalse(app(SmartCaptchaVerifier::class)->verify(''));
        $this->assertFalse(app(SmartCaptchaVerifier::class)->verify(null));
    }

    public function test_rate_limiter_counts_attempts_per_email_and_ip(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->attemptWrongPassword($userA, 3);

        // У другого email счётчик не пострадал — капча не требуется.
        Volt::test('pages.auth.login')
            ->set('form.email', $userB->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticated();
    }
}
