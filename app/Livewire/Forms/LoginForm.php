<?php

namespace App\Livewire\Forms;

use App\Services\SmartCaptchaVerifier;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    /**
     * После скольких неверных попыток пароля требовать SmartCaptcha —
     * эпик 31 дорожной карты (Веха 3). Меньше лимита блокировки RateLimiter
     * (5 попыток, см. ensureIsNotRateLimited) — капча должна появляться
     * раньше полной блокировки, а не вместо неё.
     */
    public const CAPTCHA_AFTER_ATTEMPTS = 3;

    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    public string $captchaToken = '';

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if ($this->requiresCaptcha()) {
            $this->ensureCaptchaIsValid();
        }

        if (! Auth::attempt($this->only(['email', 'password']), $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Нужно ли показывать/проверять SmartCaptcha — после 3 неверных попыток
     * пароля для текущего email+IP. Публичный метод — используется и в
     * Blade (Volt-компонент login), чтобы решить, рендерить ли виджет.
     */
    public function requiresCaptcha(): bool
    {
        return RateLimiter::attempts($this->throttleKey()) >= self::CAPTCHA_AFTER_ATTEMPTS;
    }

    /**
     * @throws ValidationException
     */
    protected function ensureCaptchaIsValid(): void
    {
        if (app(SmartCaptchaVerifier::class)->verify($this->captchaToken)) {
            return;
        }

        throw ValidationException::withMessages([
            'form.captchaToken' => 'Пройдите проверку «Я не робот», чтобы продолжить.',
        ]);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
