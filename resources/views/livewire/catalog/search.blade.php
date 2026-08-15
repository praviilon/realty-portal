<div>
    <div class="flex flex-wrap gap-4 mb-6 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Сделка</label>
            <select wire:model.live="dealType" class="rounded-lg border-gray-300">
                <option value="sale">Купить</option>
                <option value="rent">Снять</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Цена от</label>
            <input type="number" wire:model.live.debounce.500ms="priceMin" class="rounded-lg border-gray-300 w-32">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Цена до</label>
            <input type="number" wire:model.live.debounce.500ms="priceMax" class="rounded-lg border-gray-300 w-32">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" wire:loading.class="opacity-50">
        @forelse ($listings as $listing)
            <a href="{{ route('residential.show', $listing) }}" class="block border rounded-xl p-4 hover:shadow-lg transition">
                <div class="font-semibold text-lg">{{ number_format($listing->price, 0, '', ' ') }} ₽</div>
                <div class="text-gray-600 text-sm">{{ $listing->address }}</div>
                <div class="text-gray-500 text-sm">{{ $listing->area }} м² · этаж {{ $listing->floor }}/{{ $listing->total_floors }}</div>
            </a>
        @empty
            <p class="text-gray-500 col-span-full">Ничего не найдено по заданным фильтрам.</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $listings->links() }}
    </div>
</div>
