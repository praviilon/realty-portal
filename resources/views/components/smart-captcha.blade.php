@props(['model' => 'form.captchaToken'])

{{-- Яндекс SmartCaptcha — эпик 31 (Веха 3), появляется после 3 неверных
     попыток пароля (см. App\Livewire\Forms\LoginForm::requiresCaptcha()). --}}
<div class="mt-4">
    @if (config('services.smartcaptcha.site_key'))
        <div
            class="smart-captcha"
            data-sitekey="{{ config('services.smartcaptcha.site_key') }}"
            data-callback="smartCaptchaTokenReceived"
        ></div>
        <script src="https://smartcaptcha.yandexcloud.net/captcha.js" defer></script>
        <script>
            function smartCaptchaTokenReceived(token) {
                window.Livewire.first().set('{{ $model }}', token);
            }
        </script>
    @else
        <div class="text-xs text-amber-700 border border-amber-200 bg-amber-50 rounded-lg p-3">
            Капча недоступна: не задан <code class="mx-1">SMARTCAPTCHA_SITE_KEY</code> в .env
            (см. раздел 7.5 технического плана). Для продолжения введите любое значение
            в поле ниже (временная заглушка для разработки).
            <input type="text" wire:model="{{ $model }}" class="mt-2 w-full rounded-lg border-gray-300 text-sm" placeholder="токен капчи (заглушка)">
        </div>
    @endif
</div>
