<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Кириллица, пробел, дефис; 2–40 символов (см. раздел 1 технического плана).
 * Автокапитализация выполняется отдельно, см. App\Support\NameFormatter.
 */
class RussianName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(trans('validation.russian_name'));

            return;
        }

        $length = mb_strlen($value);

        if ($length < 2 || $length > 40) {
            $fail(trans('validation.russian_name'));

            return;
        }

        if (! preg_match('/^[а-яА-ЯёЁ]+(?:[ -][а-яА-ЯёЁ]+)*$/u', $value)) {
            $fail(trans('validation.russian_name'));
        }
    }
}
