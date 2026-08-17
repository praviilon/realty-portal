@props(['id' => 'newPhotos', 'model' => 'newPhotos'])

{{-- Кликабельная область выбора фото — раньше это был голый
     <input type="file">, из-за чего область выглядела как пустое
     кликабельное пространство без какой-либо иконки/подсказки. --}}
<label for="{{ $id }}"
       {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-2 w-full h-32 rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 text-gray-500 hover:bg-gray-100 hover:border-primary-400 cursor-pointer transition']) }}>
    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 8.25L12 3.75m0 0L7.5 8.25M12 3.75v13.5" />
    </svg>
    <span class="text-sm">Нажмите, чтобы выбрать фотографии</span>
    <input type="file" wire:model="{{ $model }}" id="{{ $id }}" multiple accept="image/*" class="hidden">
</label>
