<div class="relative">
    <button
        type="button"
        wire:click.stop.prevent="toggle"
        aria-label="{{ $isAdded ? 'Убрать из сравнения' : 'Добавить к сравнению' }}"
        title="{{ $isAdded ? 'Убрать из сравнения' : 'Добавить к сравнению' }}"
        @class([
            'inline-flex items-center justify-center w-9 h-9 rounded-full shadow transition',
            'bg-blue-50 text-blue-600 hover:bg-blue-100' => $isAdded,
            'bg-white text-gray-400 hover:text-blue-500' => ! $isAdded,
        ])
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M8 17V9m4 8V5m4 12v-6" />
        </svg>
    </button>

    @if ($limitMessage)
        <div class="absolute z-30 top-11 right-0 w-56 text-xs bg-gray-900 text-white rounded-lg p-2 shadow-lg">
            {{ $limitMessage }}
        </div>
    @endif
</div>
