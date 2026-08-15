<div>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Сообщения</h1>

        @if ($chats->isEmpty())
            <p class="text-gray-500">У вас пока нет диалогов. Напишите продавцу с карточки объявления.</p>
        @else
            <div class="bg-white rounded-xl shadow divide-y">
                @foreach ($chats as $chat)
                    @php($other = $chat->otherParticipant(auth()->id()))
                    @php($lastMessage = $chat->messages->first())
                    <a href="{{ route('chat.show', $chat) }}" wire:navigate class="flex items-center justify-between gap-4 p-4 hover:bg-gray-50">
                        <div>
                            <div class="font-medium">{{ $other->full_name }}</div>
                            <div class="text-sm text-gray-500">{{ $chat->listable?->address ?? 'Объявление удалено' }}</div>
                            @if ($lastMessage)
                                <div class="text-sm text-gray-400 truncate max-w-xs">{{ $lastMessage->text }}</div>
                            @endif
                        </div>
                        @if ($chat->unread_count > 0)
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-semibold">
                                {{ $chat->unread_count }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
