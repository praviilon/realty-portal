<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('Аватар') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Квадратное изображение 256×256, автоматически обрезается и сохраняется в формате WebP.') }}</p>
    </header>

    <div class="mt-4 flex items-center gap-6">
        <div class="shrink-0">
            @if ($avatar && $avatar->isPreviewable())
                <img src="{{ $avatar->temporaryUrl() }}" class="w-24 h-24 rounded-full object-cover border">
            @elseif (auth()->user()->avatar_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::url(auth()->user()->avatar_path) }}" class="w-24 h-24 rounded-full object-cover border">
            @else
                <div class="w-24 h-24 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-2xl font-semibold">
                    {{ mb_substr(auth()->user()->first_name, 0, 1) }}
                </div>
            @endif
        </div>

        <div class="space-y-2">
            <label class="inline-block">
                <span class="sr-only">Выбрать файл</span>
                <input type="file" wire:model="avatar" accept="image/*" class="block text-sm text-gray-600">
            </label>
            <x-input-error :messages="$errors->get('avatar')" class="mt-1" />
            <div wire:loading wire:target="avatar" class="text-xs text-gray-400">Загрузка и обработка...</div>

            @if (auth()->user()->avatar_path)
                <button type="button" wire:click="removeAvatar" class="text-sm text-red-600 hover:underline">
                    Удалить аватар
                </button>
            @endif
        </div>
    </div>
</section>
