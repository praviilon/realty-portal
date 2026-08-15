<div wire:poll.10s x-data="{ open: false }" class="relative">
    <button @click="open = !open" type="button" class="relative p-2 text-gray-500 hover:text-gray-800 focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if ($unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-4 h-4 rounded-full bg-red-600 text-white text-[10px] font-semibold">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" @click.outside="open = false" x-cloak
         class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-100 z-50">
        <div class="flex items-center justify-between px-4 py-2 border-b">
            <span class="text-sm font-semibold text-gray-700">Уведомления</span>
            @if ($unreadCount > 0)
                <button wire:click="markAllAsRead" class="text-xs text-blue-600 hover:underline">Прочитать все</button>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto divide-y">
            @forelse ($notifications as $notification)
                <button wire:click="markAsRead('{{ $notification->id }}')"
                        class="w-full text-left px-4 py-3 text-sm hover:bg-gray-50 {{ is_null($notification->read_at) ? 'bg-blue-50' : '' }}">
                    <div class="text-gray-700">{{ $notification->data['message'] ?? '' }}</div>
                    <div class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                </button>
            @empty
                <p class="px-4 py-6 text-sm text-gray-400 text-center">Уведомлений пока нет.</p>
            @endforelse
        </div>
    </div>
</div>
