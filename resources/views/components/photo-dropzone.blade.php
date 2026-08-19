@props(['id' => 'newPhotos', 'model' => 'incomingPhotos', 'remaining' => 5])

{{-- Кликабельная область выбора фото.

     ИЗМЕНЕНО (по просьбе пользователя, исправление багов загрузки фото):
     1) wire:model раньше был напрямую привязан к накопительному массиву
        $newPhotos — при повторном выборе файлов Livewire ПОЛНОСТЬЮ
        заменяет значение свойства новым списком файлов, поэтому уже
        выбранные/загруженные фото пропадали из интерфейса и не попадали
        в объявление. Теперь input привязан к промежуточному свойству
        (по умолчанию $incomingPhotos), которое компонент мастера
        объединяет с уже накопленными фото в updatedIncomingPhotos()
        (см. App\Livewire\Property\CreateWizard и аналогичные классы для
        коммерческой недвижимости и рабочих пространств), а не заменяет.
     2) Ограничение в 5 фото на объявление — раньше можно было выбрать
        произвольное количество файлов за один раз, что при большом
        количестве могло приводить к падению страницы (503) на этапе
        загрузки. Здесь на событии change (в фазе capture — раньше, чем
        сработает собственный обработчик Livewire) обрезаем список файлов
        до оставшегося лимита ДО того, как Livewire начнёт их загружать. --}}
<div x-data="{ limitMessage: null }">
    <label for="{{ $id }}"
           {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-2 w-full h-32 rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 text-gray-500 hover:bg-gray-100 hover:border-primary-400 cursor-pointer transition']) }}>
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 8.25L12 3.75m0 0L7.5 8.25M12 3.75v13.5" />
        </svg>
        <span class="text-sm">Нажмите, чтобы выбрать фотографии</span>
        <input type="file" wire:model="{{ $model }}" id="{{ $id }}" multiple accept="image/*" class="hidden"
               x-on:change.capture="
                   const remaining = {{ (int) $remaining }};
                   const files = $event.target.files;
                   if (files && files.length > remaining) {
                       const dt = new DataTransfer();
                       for (let i = 0; i < remaining; i++) { dt.items.add(files[i]); }
                       $event.target.files = dt.files;
                       limitMessage = remaining > 0
                           ? `Можно загрузить ещё не более ${remaining} фото за раз — лишние файлы не выбраны.`
                           : 'Достигнут лимит 5 фотографий на одно объявление — сначала удалите часть фото.';
                       setTimeout(() => (limitMessage = null), 6000);
                   }
               ">
    </label>
    <p x-show="limitMessage" x-text="limitMessage" x-cloak class="text-xs text-red-600 mt-1"></p>
</div>
