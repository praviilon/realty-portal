<x-filament-panels::page>
    <div class="space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Здесь можно вручную запустить очистку данных, которые могли накопиться в базе и файловом
            хранилище: фотографии объявлений, у которых само объявление уже удалено, файлы в
            хранилище без соответствующей записи в базе, и уведомления удалённых пользователей.
            После удаления объявления или пользователя эти данные теперь удаляются автоматически —
            эта страница нужна в основном для того, что успело накопиться раньше, либо как
            подстраховка на будущее.
        </p>

        <x-filament::button wire:click="runCleanup" wire:loading.attr="disabled" icon="heroicon-o-trash">
            Запустить очистку
        </x-filament::button>

        @if ($lastResult)
            <div class="mt-4 rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-sm space-y-1">
                <div>Удалено осиротевших фото (запись + файл): <strong>{{ $lastResult['orphaned_photos'] }}</strong></div>
                <div>Удалено файлов без записи в базе: <strong>{{ $lastResult['orphaned_files'] }}</strong></div>
                <div>Удалено осиротевших уведомлений: <strong>{{ $lastResult['orphaned_notifications'] }}</strong></div>
                <div>Освобождено места: <strong>{{ $lastResult['freed_human'] }}</strong></div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
