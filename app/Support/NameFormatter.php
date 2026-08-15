<?php

namespace App\Support;

class NameFormatter
{
    /**
     * Автокапитализация имени/фамилии: первая буква каждого сегмента (по пробелу
     * и дефису) — заглавная, остальные — строчные. Пример: "иванов-петров" → "Иванов-Петров".
     */
    public static function capitalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return $value;
        }

        $value = mb_strtolower(trim($value));

        // Капитализируем после начала строки, пробела и дефиса.
        return preg_replace_callback(
            '/(^|[ -])([а-яё])/u',
            fn (array $matches) => $matches[1] . mb_strtoupper($matches[2]),
            $value
        );
    }
}
