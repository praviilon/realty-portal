<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center gap-4 mb-6 flex-wrap">
            <h1 class="text-2xl font-bold text-gray-900">Рабочие пространства</h1>
            <a href="{{ route('residential.search') }}" wire:navigate class="text-sm text-blue-600 hover:underline">Жилая недвижимость &rarr;</a>
            <a href="{{ route('commercial.search') }}" wire:navigate class="text-sm text-blue-600 hover:underline">Коммерция &rarr;</a>
        </div>

        <!-- Все фильтры -->
        <div class="bg-white rounded-xl shadow p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Тип</label>
                    <select wire:model.live="workspaceType" class="rounded-lg border-gray-300 text-sm">
                        <option value="">Любой</option>
                        @foreach ($workspaceTypeLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Период оплаты</label>
                    <select wire:model.live="period" class="rounded-lg border-gray-300 text-sm">
                        @foreach ($periodLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Цена от</label>
                    <input type="number" wire:model.live.debounce.500ms="priceMin" class="rounded-lg border-gray-300 w-28 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Цена до</label>
                    <input type="number" wire:model.live.debounce.500ms="priceMax" class="rounded-lg border-gray-300 w-28 text-sm">
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
                @foreach ($amenityLabels as $value => $label)
                    <label class="inline-flex items-center gap-1.5 text-sm text-gray-700">
                        <input type="checkbox" wire:model.live="amenities" value="{{ $value }}" class="rounded border-gray-300">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="text-sm text-gray-500 mb-4">
            Найдено объявлений: {{ $listings->total() }}
        </div>

        <!-- Каталог/карта: фиксированная колонка (эпик 26) — карта всегда видна рядом
             со списком, без переключателя список/карта, в отличие от жилой и
             коммерческой недвижимости. -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6" wire:loading.class="opacity-50">
                    @forelse ($listings as $listing)
                        <div class="relative bg-white border rounded-xl p-4 hover:shadow-lg transition">
                            <a href="{{ route('workspace.show', $listing) }}" wire:navigate class="absolute inset-0 z-0" aria-label="Открыть объявление"></a>

                            <div class="relative z-10 pointer-events-none">
                                <div class="flex items-center justify-between pr-10">
                                    <div class="font-semibold text-lg">
                                        от {{ number_format($listing->display_price ?? 0, 0, '', ' ') }} ₽
                                    </div>
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $listing->workspace_type_label }}</span>
                                </div>
                                <div class="text-gray-600 text-sm mt-1">{{ $listing->address }}</div>
                                <div class="text-gray-500 text-sm">{{ $listing->area }} м²</div>
                            </div>

                            <div class="absolute top-3 right-3 z-20 flex flex-col gap-2">
                                <livewire:favorites.button :favoritable="$listing" :key="'fav-workspace-'.$listing->id" />
                                <livewire:comparison.button :comparable="$listing" :key="'cmp-workspace-'.$listing->id" />
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

            <div class="lg:col-span-1">
                <div class="sticky top-6">
                    <x-yandex-map :pins="$pins" />
                </div>
            </div>
        </div>
    </div>
</div>
