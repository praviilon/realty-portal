@props(['pins' => [], 'selectable' => false])

@if (config('services.yandex_maps.api_key'))
    {{-- x-init вызывает initMap(), а не init() — см. подробный комментарий
         в resources/js/yandex-map.js: имя `init` зарезервировано Alpine
         и автоматически вызывается им без аргументов, что раньше ломало
         инициализацию карты (el === undefined при автовызове). --}}
    <div wire:ignore x-data="yandexMap(@js($pins), @js(config('services.yandex_maps.api_key')), @js($selectable))" x-init="initMap($refs.mapCanvas)">
        @if ($selectable)
            {{-- Эпик 19 (Веха 2): выделение области на карте (MySQL spatial) --}}
            <div class="flex flex-wrap items-center gap-3 mb-2 text-sm">
                <button
                    type="button"
                    @click="toggleAreaSelection()"
                    :disabled="!mapReady"
                    x-text="selectingArea ? 'Отменить выделение' : 'Выделить область на карте'"
                    :class="selectingArea ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-600 border-gray-300'"
                    class="px-3 py-1.5 rounded-lg border transition disabled:opacity-50 disabled:cursor-not-allowed"
                ></button>
                <button type="button" x-show="hasAreaSelection" x-cloak @click="clearAreaSelection()" class="text-gray-500 hover:text-red-600 underline">
                    Сбросить область
                </button>
                <span x-show="selectingArea" x-cloak class="text-gray-400">
                    Кликните по двум противоположным углам области на карте
                </span>
            </div>
        @endif

        {{-- Пока грузится сторонний скрипт Yandex Maps, показываем явную
             подсказку вместо пустого прямоугольника — без этого при
             медленной сети было непонятно, карта сломана или просто ещё
             не успела загрузиться. ВАЖНО: сам x-ref="mapCanvas" всегда
             остаётся в DOM видимым (без x-show/display:none) — YMap()
             вычисляет размеры контейнера в момент создания, и если бы этот
             элемент был скрыт через display:none, карта могла бы
             инициализироваться с нулевыми размерами. Поэтому подсказка о
             загрузке рисуется отдельным слоем ПОВЕРХ карты, а не вместо неё. --}}
        <div class="relative">
            <div x-ref="mapCanvas" {{ $attributes->merge(['class' => 'w-full h-96 rounded-xl overflow-hidden border border-gray-200']) }}></div>
            {{-- ИСПРАВЛЕНО (разбор регрессии "DomContext: attaching to
                 entity with destroyed DomContext" — см. createMapWithRetry()
                 в resources/js/yandex-map.js): раньше при сбое создания
                 карты плейсхолдер "Загрузка карты…" не снимался никогда.
                 Теперь после нескольких неудачных попыток показываем явное
                 сообщение об ошибке с кнопкой "Обновить страницу" вместо
                 вечной надписи о загрузке. --}}
            <div x-show="!mapReady && !mapFailed" x-cloak class="absolute inset-0 w-full h-96 rounded-xl border border-gray-200 flex items-center justify-center text-gray-400 text-sm bg-gray-50">
                Загрузка карты…
            </div>
            <div x-show="mapFailed" x-cloak class="absolute inset-0 w-full h-96 rounded-xl border border-gray-200 flex flex-col items-center justify-center gap-2 text-gray-500 text-sm bg-gray-50 text-center p-4">
                <span>Не удалось загрузить карту.</span>
                <button type="button" @click="location.reload()" class="text-primary-600 hover:underline">Обновить страницу</button>
            </div>
        </div>
    </div>
@else
    <div {{ $attributes->merge(['class' => 'w-full h-96 rounded-xl border border-dashed border-gray-300 flex items-center justify-center text-gray-400 text-sm text-center p-6']) }}>
        Карта недоступна: не задан <code class="mx-1">YANDEX_MAPS_API_KEY</code> в .env
        (см. раздел 7.5 технического плана).
    </div>
@endif
