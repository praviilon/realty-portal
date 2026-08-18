<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Формат +7 (XXX) XXX-XX-XX (см. раздел 1 технического плана).
 *
 * Локаль сообщения об ошибке жёстко зафиксирована на 'ru' третьим аргументом
 * trans(...). Раньше сообщение бралось из локали текущего запроса
 * (config('app.locale')): если на сервере APP_LOCALE/APP_FALLBACK_LOCALE не
 * был выставлен в 'ru' (например, .env скопирован с англоязычными
 * значениями по умолчанию из .env.example), trans() не находил перевод и
 * возвращал сам ключ как есть — пользователь видел "validation.russian_phone"
 * вместо понятного текста. Явная локаль убирает эту зависимость от
 * окружения полностью: сайт русскоязычный, и это сообщение должно быть
 * на русском независимо от конфигурации сервера.
 */
class RussianPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/', $value)) {
            $fail(trans('validation.russian_phone', [], 'ru'));
        }
    }
}
