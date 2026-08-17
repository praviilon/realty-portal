<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Проверка токена Яндекс SmartCaptcha — эпик 31 дорожной карты (Веха 3).
 * Виджет на фронте выдаёт токен, который здесь сверяется через серверный
 * ключ (раздел 1 плана: "виджет + серверная проверка токена через
 * Http::asForm()->post()").
 *
 * Как и у карт (YANDEX_MAPS_API_KEY, см. resources/views/components/yandex-map.blade.php),
 * ключ SMARTCAPTCHA_SERVER_KEY не задан в .env этого окружения — без него
 * проверка не блокирует форму (иначе разработка/тесты были бы невозможны
 * без реального обращения к API Яндекса), достаточно непустого токена.
 */
class SmartCaptchaVerifier
{
    public function verify(?string $token): bool
    {
        $serverKey = config('services.smartcaptcha.server_key');

        if (! $serverKey) {
            return filled($token);
        }

        if (blank($token)) {
            return false;
        }

        $response = Http::asForm()->post('https://smartcaptcha.yandexcloud.net/validate', [
            'secret' => $serverKey,
            'token' => $token,
            'ip' => request()->ip(),
        ]);

        return $response->successful() && $response->json('status') === 'ok';
    }
}
