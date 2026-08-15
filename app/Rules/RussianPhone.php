<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Формат +7 (XXX) XXX-XX-XX (см. раздел 1 технического плана).
 */
class RussianPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/', $value)) {
            $fail(trans('validation.russian_phone'));
        }
    }
}
