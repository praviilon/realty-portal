<button
    type="button"
    wire:click.stop.prevent="toggle"
    wire:key="favorite-btn-{{ $favoritable::class }}-{{ $favoritable->id }}"
    aria-label="{{ $isFavorited ? 'Убрать из избранного' : 'В избранное' }}"
    title="{{ $isFavorited ? 'Убрать из избранного' : 'В избранное' }}"
    @class([
        'inline-flex items-center justify-center w-9 h-9 rounded-full shadow transition',
        'bg-red-50 text-red-600 hover:bg-red-100' => $isFavorited,
        'bg-white text-gray-400 hover:text-red-500' => ! $isFavorited,
    ])
>
    @if ($isFavorited)
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
            <path d="M9.653 16.915l-.005-.003-.019-.01a20.759 20.759 0 01-1.162-.682 22.045 22.045 0 01-2.582-1.9C4.045 12.733 2 10.352 2 7.5 2 5.015 3.99 3 6.45 3c1.397 0 2.63.63 3.55 1.674C10.92 3.63 12.153 3 13.55 3 16.01 3 18 5.015 18 7.5c0 2.852-2.044 5.233-3.885 6.82a22.049 22.049 0 01-3.744 2.582l-.019.01-.005.003h-.002a.739.739 0 01-.69.001l-.002-.001z" />
        </svg>
    @else
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.636l1.318-1.318a4.5 4.5 0 116.364 6.364L12 21l-7.682-7.682a4.5 4.5 0 010-6.364z" />
        </svg>
    @endif
</button>
