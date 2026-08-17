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
        x-init="init()"
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

        <div x-ref="pickerMap" class="w-full h-56 rounded-xl overflow-hidden border border-gray-200 mt-3"></div>

        <p class="text-xs text-gray-400 mt-2">
            Выберите адрес из подсказок или кликните по карте, чтобы уточнить точку — координаты подставятся автоматически.
        </p>
    </div>
@else
    <p class="text-xs text-gray-400 mt-3">
        Подбор адреса по карте недоступен: не задан <code class="mx-1">YANDEX_MAPS_API_KEY</code> в .env
        (см. раздел 7.5 технического плана) — координаты можно ввести вручную ниже.
    </p>
@endif
