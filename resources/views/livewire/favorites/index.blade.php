<div>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Избранное</h1>

        <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden text-sm mb-6">
            <button type="button" wire:click="setTab('residential')"
                    class="px-4 py-2 {{ $tab === 'residential' ? 'bg-gray-800 text-white' : 'bg-white text-gray-600' }}">
                Жильё ({{ $counts['residential'] ?? 0 }})
            </button>
            <button type="button" wire:click="setTab('commercial')"
                    class="px-4 py-2 {{ $tab === 'commercial' ? 'bg-gray-800 text-white' : 'bg-white text-gray-600' }}">
                Коммерция ({{ $counts['commercial'] ?? 0 }})
            </button>
            <button type="button" wire:click="setTab('workspace')"
                    class="px-4 py-2 {{ $tab === 'workspace' ? 'bg-gray-800 text-white' : 'bg-white text-gray-600' }}">
                Рабочие пространства ({{ $counts['workspace'] ?? 0 }})
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" wire:loading.class="opacity-50">
            @forelse ($favorites as $favorite)
                @php($listing = $favorite->favoritable)
                @php($showRoute = match ($tab) { 'residential' => route('residential.show', $listing), 'commercial' => route('commercial.show', $listing), default => route('workspace.show', $listing) })
                @php($price = match ($tab) { 'residential' => $listing->price, default => $listing->display_price })
                <div class="relative bg-white border rounded-xl p-4 hover:shadow-lg transition">
                    <a href="{{ $showRoute }}" wire:navigate class="absolute inset-0 z-0" aria-label="Открыть объявление"></a>

                    <div class="relative z-10 flex items-start justify-between gap-3 pointer-events-none">
                        <div class="flex gap-3 min-w-0">
                            <x-listing-thumb :photo="$listing->mainPhoto" />
                            <div class="min-w-0">
                                <div class="font-semibold text-lg">
                                    {{ $tab === 'workspace' ? 'от ' : '' }}{{ number_format($price ?? 0, 0, '', ' ') }} ₽{{ $tab === 'commercial' && $listing->deal_type === 'rent' ? '/мес.' : '' }}
                                </div>
                                <div class="text-gray-600 text-sm mt-1">{{ $listing->address }}</div>
                                <div class="text-gray-500 text-sm">{{ $listing->area }} м²</div>
                            </div>
                        </div>

                        <div class="pointer-events-auto">
                            <button type="button" wire:click="removeFavorite({{ $favorite->id }})"
                                    class="text-sm text-gray-400 hover:text-red-600" title="Убрать из избранного">
                                &times; Убрать
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 col-span-full">
                    @if ($tab === 'residential')
                        В избранном пока нет жилой недвижимости.
                    @elseif ($tab === 'commercial')
                        В избранном пока нет коммерческой недвижимости.
                    @else
                        В избранном пока нет рабочих пространств.
                    @endif
                </p>
            @endforelse
        </div>
    </div>
</div>
