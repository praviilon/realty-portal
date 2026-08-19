<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center gap-4 mb-6 flex-wrap">
            <h1 class="text-2xl font-bold text-gray-900">Каталог жилой недвижимости</h1>
            <a href="{{ route('commercial.search') }}" wire:navigate class="text-sm text-primary-600 hover:underline">Коммерция &rarr;</a>
            <a href="{{ route('workspace.search') }}" wire:navigate class="text-sm text-primary-600 hover:underline">Рабочие пространства &rarr;</a>
        </div>

        <div class="bg-white rounded-xl shadow p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Сделка</label>
                    <select wire:model.live="dealType" class="rounded-lg border-gray-300 text-sm">
                        <option value="sale">Купить</option>
                        <option value="rent">Снять</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Тип</label>
                    <select wire:model.live="propertyType" class="rounded-lg border-gray-300 text-sm">
                        <option value="">Любой</option>
                        @foreach ($propertyTypeLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Цена от</label>
                    <input type="number" wire:model.live.debounce.500ms="priceMin" class="rounded-lg border-gray-300 w-32 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Цена до</label>
                    <input type="number" wire:model.live.debounce.500ms="priceMax" class="rounded-lg border-gray-300 w-32 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Площадь от, м²</label>
                    <input type="number" wire:model.live.debounce.500ms="areaMin" class="rounded-lg border-gray-300 w-24 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Площадь до, м²</label>
                    <input type="number" wire:model.live.debounce.500ms="areaMax" class="rounded-lg border-gray-300 w-24 text-sm">
                </div>
                <div>
                    <button type="button" wire:click="resetFilters" class="text-sm text-gray-500 hover:text-gray-800 underline">
                        Сбросить фильтры
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 mt-4 pt-4 border-t">
                @foreach ($floorFeatureLabels as $value => $label)
                    <label class="inline-flex items-center gap-1.5 text-sm text-gray-700">
                        <input type="checkbox" wire:model.live="floorFeatures" value="{{ $value }}" class="rounded border-gray-300">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-between mb-4">
            <div class="text-sm text-gray-500">
                Найдено объявлений: {{ $listings->total() }}
            </div>

            <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden text-sm">
                <button type="button" wire:click="$set('view', 'list')"
                        class="px-3 py-1.5 {{ $view === 'list' ? 'bg-gray-800 text-white' : 'bg-white text-gray-600' }}">
                    Список
                </button>
                <button type="button" wire:click="$set('view', 'map')"
                        class="px-3 py-1.5 {{ $view === 'map' ? 'bg-gray-800 text-white' : 'bg-white text-gray-600' }}">
                    Карта
                </button>
            </div>
        </div>

        @if ($view === 'map')
            <x-yandex-map :pins="$pins" :selectable="true" class="mb-6" />

            @if (count($areaPolygon) >= 3)
                <p class="text-sm text-gray-500 -mt-4 mb-6">
                    Показаны объявления в выделенной на карте области.
                    <button type="button" wire:click="clearAreaSelection" class="text-primary-600 hover:underline">Сбросить область</button>
                </p>
            @endif
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" wire:loading.class="opacity-50">
            @forelse ($listings as $listing)
                <div class="relative bg-white border rounded-xl p-4 hover:shadow-lg transition">
                    <a href="{{ route('residential.show', $listing) }}" wire:navigate class="absolute inset-0 z-0" aria-label="Открыть объявление"></a>

                    <div class="relative z-10 pointer-events-none flex gap-3">
                        <x-listing-thumb :photo="$listing->mainPhoto" />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between pr-10">
                                <div class="font-semibold text-lg">{{ number_format($listing->price, 0, '', ' ') }} ₽</div>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $listing->property_type_label }}</span>
                            </div>
                            <div class="text-gray-600 text-sm mt-1">{{ $listing->address }}</div>
                            <div class="text-gray-500 text-sm">{{ $listing->area }} м² · этаж {{ $listing->floor }}/{{ $listing->total_floors }}</div>
                        </div>
                    </div>

                    <div class="absolute top-3 right-3 z-20 flex flex-col gap-2">
                        <livewire:favorites.button :favoritable="$listing" :key="'fav-residential-'.$listing->id" />
                        <livewire:comparison.button :comparable="$listing" :key="'cmp-residential-'.$listing->id" />
                    </div>
                </div>
            @empty
                <p class="text-gray-500 col-span-full">Ничего не найдено по заданным фильтрам.</p>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $listings->links() }}
        </div>
    </div>
</div>
