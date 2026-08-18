<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 6–60 символов, латиница, обязательны заглавные + строчные буквы + цифры
 * (см. раздел 1 технического плана).
 *
 * Локаль сообщения жёстко зафиксирована на 'ru' (см. подробное объяснение
 * в App\Rules\RussianPhone) — сообщение не должно зависеть от того, как
 * настроен APP_LOCALE/APP_FALLBACK_LOCALE на сервере.
 */
class PasswordPolicy implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(trans('validation.password_policy', [], 'ru'));

            return;
        }

        $length = mb_strlen($value);

        if ($length < 6 || $length > 60) {
            $fail(trans('validation.password_policy', [], 'ru'));

            return;
        }

        if (! preg_match('/^[A-Za-z\d]+$/', $value)) {
            $fail(trans('validation.password_policy', [], 'ru'));

            return;
        }

        if (! preg_match('/[a-z]/', $value) || ! preg_match('/[A-Z]/', $value) || ! preg_match('/\d/', $value)) {
            $fail(trans('validation.password_policy', [], 'ru'));
        }
    }
}
