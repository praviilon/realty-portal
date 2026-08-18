<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Кириллица, пробел, дефис; 2–40 символов (см. раздел 1 технического плана).
 * Автокапитализация выполняется отдельно, см. App\Support\NameFormatter.
 *
 * Локаль сообщения жёстко зафиксирована на 'ru' (см. подробное объяснение
 * в App\Rules\RussianPhone) — сообщение не должно зависеть от того, как
 * настроен APP_LOCALE/APP_FALLBACK_LOCALE на сервере.
 */
class RussianName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(trans('validation.russian_name', [], 'ru'));

            return;
        }

        $length = mb_strlen($value);

        if ($length < 2 || $length > 40) {
            $fail(trans('validation.russian_name', [], 'ru'));

            return;
        }

        if (! preg_match('/^[а-яА-ЯёЁ]+(?:[ -][а-яА-ЯёЁ]+)*$/u', $value)) {
            $fail(trans('validation.russian_name', [], 'ru'));
        }
    }
}
