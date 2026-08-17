@props(['pins' => [], 'selectable' => false])

@if (config('services.yandex_maps.api_key'))
    <div wire:ignore x-data="yandexMap(@js($pins), @js(config('services.yandex_maps.api_key')), @js($selectable))" x-init="init($refs.mapCanvas)">
        @if ($selectable)
            {{-- Эпик 19 (Веха 2): выделение области на карте (MySQL spatial) --}}
            <div class="flex flex-wrap items-center gap-3 mb-2 text-sm">
                <button
                    type="button"
                    @click="toggleAreaSelection()"
                    x-text="selectingArea ? 'Отменить выделение' : 'Выделить область на карте'"
                    :class="selectingArea ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300'"
                    class="px-3 py-1.5 rounded-lg border transition"
                ></button>
                <button type="button" x-show="hasAreaSelection" x-cloak @click="clearAreaSelection()" class="text-gray-500 hover:text-red-600 underline">
                    Сбросить область
                </button>
                <span x-show="selectingArea" x-cloak class="text-gray-400">
                    Кликните по двум противоположным углам области на карте
                </span>
            </div>
        @endif

        <div x-ref="mapCanvas" {{ $attributes->merge(['class' => 'w-full h-96 rounded-xl overflow-hidden border border-gray-200']) }}></div>
    </div>
@else
    <div {{ $attributes->merge(['class' => 'w-full h-96 rounded-xl border border-dashed border-gray-300 flex items-center justify-center text-gray-400 text-sm text-center p-6']) }}>
        Карта недоступна: не задан <code class="mx-1">YANDEX_MAPS_API_KEY</code> в .env
        (см. раздел 7.5 технического плана).
    </div>
@endif
