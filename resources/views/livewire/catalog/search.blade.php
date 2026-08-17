<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Каталог жилой недвижимости</h1>

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
                    <button type="button" wire:click="resetFilters" class="text-sm text-gray-500 hover:text-gray-800 underline">
                        Сбросить фильтры
                    </button>
                </div>
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
            <x-yandex-map :pins="$pins" class="mb-6" />
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" wire:loading.class="opacity-50">
            @forelse ($listings as $listing)
                <div class="relative bg-white border rounded-xl p-4 hover:shadow-lg transition">
                    <a href="{{ route('residential.show', $listing) }}" wire:navigate class="absolute inset-0 z-0" aria-label="Открыть объявление"></a>

                    <div class="relative z-10 pointer-events-none">
                        <div class="flex items-center justify-between pr-10">
                            <div class="font-semibold text-lg">{{ number_format($listing->price, 0, '', ' ') }} ₽</div>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $listing->property_type_label }}</span>
                        </div>
                        <div class="text-gray-600 text-sm mt-1">{{ $listing->address }}</div>
                        <div class="text-gray-500 text-sm">{{ $listing->area }} м² · этаж {{ $listing->floor }}/{{ $listing->total_floors }}</div>
                    </div>

                    <div class="absolute top-3 right-3 z-20">
                        <livewire:favorites.button :favoritable="$listing" :key="'fav-residential-'.$listing->id" />
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
