@props(['address' => '', 'lat' => null, 'lng' => null])

{{--
    Эпик 20 (Веха 2): выбор адреса при создании объявления (геокодер).
    Дополняет обычный текстовый ввод адреса (см. wire:model="address" рядом
    в шаблоне мастера) — подсказки по мере набора и уточнение точки кликом
    по карте, оба способа пишут значение напрямую в свойства Livewire-
    компонента ($wire.set), поэтому обычная ручная правка полей "Адрес",
    "Широта", "Долгота" продолжает работать как раньше.
--}}
@if (config('services.yandex_maps.api_key'))
    <div
        wire:ignore
        x-data="addressGeocoder(@js($address), @js($lat), @js($lng), @js(config('services.yandex_maps.api_key')))"
        x-init="initMap()"
        class="mt-3"
    >
        <div class="relative">
            <input
                type="text"
                x-model="query"
                @input.debounce.400ms="search()"
                @focus="showSuggestions = suggestions.length > 0"
                placeholder="Начните вводить адрес для подсказок..."
                class="rounded-lg border-gray-300 w-full text-sm"
            />
            <ul
                x-show="showSuggestions"
                x-cloak
                @click.outside="showSuggestions = false"
                class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-y-auto text-sm"
            >
                <template x-for="(item, index) in suggestions" :key="index">
                    <li @click="select(item)" class="px-3 py-2 hover:bg-gray-50 cursor-pointer" x-text="item.address"></li>
                </template>
            </ul>
        </div>

        {{-- Как и в yandex-map.blade.php: x-ref="pickerMap" всегда видим (без
             x-show/display:none), подсказку о загрузке рисуем поверх, чтобы
             карта не инициализировалась в контейнере нулевого размера. --}}
        <div class="relative mt-3">
            <div x-ref="pickerMap" class="w-full h-56 rounded-xl overflow-hidden border border-gray-200"></div>
            {{-- ИСПРАВЛЕНО (см. createMapWithRetry() в
                 resources/js/address-geocoder.js): при сбое создания карты
                 показываем явное сообщение вместо вечной надписи "Загрузка
                 карты…". Поля адреса/широты/долготы ниже всё равно остаются
                 редактируемыми вручную, см. подсказку под картой. --}}
            <div x-show="!mapReady && !mapFailed" x-cloak class="absolute inset-0 w-full h-56 rounded-xl border border-gray-200 flex items-center justify-center text-gray-400 text-sm bg-gray-50">
                Загрузка карты…
            </div>
            <div x-show="mapFailed" x-cloak class="absolute inset-0 w-full h-56 rounded-xl border border-gray-200 flex flex-col items-center justify-center gap-2 text-gray-500 text-sm bg-gray-50 text-center p-4">
                <span>Не удалось загрузить карту.</span>
                <button type="button" @click="location.reload()" class="text-primary-600 hover:underline">Обновить страницу</button>
            </div>
        </div>

        <p class="text-xs text-gray-400 mt-2">
            Выберите адрес из подсказок или кликните по карте, чтобы уточнить точку — координаты подставятся автоматически.
            {{-- ИЗМЕНЕНО (по просьбе пользователя): поля «Адрес», «Широта» и
                 «Долгота» выше по-прежнему нередактируемы вручную — иначе
                 пользователь мог ввести адрес без реальных координат и
                 застрять на шаге. Единственный способ задать адрес — это
                 поле ниже (подсказки) или клик по карте. --}}
            Поля «Адрес», «Широта» и «Долгота» выше заполняются только отсюда — вручную их отредактировать нельзя.
        </p>
    </div>
@else
    <p class="text-xs text-gray-400 mt-3">
        Подбор адреса по карте недоступен: не задан <code class="mx-1">YANDEX_MAPS_API_KEY</code> в .env
        (см. раздел 7.5 технического плана) — координаты можно ввести вручную ниже.
    </p>
@endif
