@props(['pins' => []])

@if (config('services.yandex_maps.api_key'))
    <div
        wire:ignore
        x-data="yandexMap(@js($pins), @js(config('services.yandex_maps.api_key')))"
        x-init="init($el)"
        {{ $attributes->merge(['class' => 'w-full h-96 rounded-xl overflow-hidden border border-gray-200']) }}
    ></div>
@else
    <div {{ $attributes->merge(['class' => 'w-full h-96 rounded-xl border border-dashed border-gray-300 flex items-center justify-center text-gray-400 text-sm text-center p-6']) }}>
        Карта недоступна: не задан <code class="mx-1">YANDEX_MAPS_API_KEY</code> в .env
        (см. раздел 7.5 технического плана).
    </div>
@endif
