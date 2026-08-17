<div wire:poll.5s="poll">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <a href="{{ route('chat.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-800">&laquo; Все диалоги</a>

        <div class="mt-4 bg-white rounded-xl shadow flex flex-col h-[32rem]">
            <div class="p-4 border-b">
                <div class="font-medium">{{ $chat->otherParticipant(auth()->id())->full_name }}</div>
                @if ($chat->listable)
                    <a href="{{ route('residential.show', $chat->listable) }}" wire:navigate class="text-sm text-primary-600 hover:underline">
                        {{ $chat->listable->address }}
                    </a>
                @endif
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                @forelse ($messages as $message)
                    <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-xs sm:max-w-sm px-3 py-2 rounded-lg text-sm {{ $message->sender_id === auth()->id() ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-800' }}">
                            <div>{{ $message->text }}</div>
                            <div class="text-[10px] opacity-70 mt-1">{{ $message->created_at->format('d.m H:i') }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-400 text-sm text-center">Сообщений пока нет — начните диалог первым.</p>
                @endforelse
            </div>

            <form wire:submit="send" class="p-4 border-t flex gap-2">
                <input type="text" wire:model="text" placeholder="Написать сообщение..." class="flex-1 rounded-lg border-gray-300 text-sm">
                <x-primary-button type="submit">Отправить</x-primary-button>
            </form>
            <x-input-error :messages="$errors->get('text')" class="px-4 pb-2" />
        </div>
    </div>
</div>
