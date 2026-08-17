@props(['photo' => null])

{{-- Мини-фото объявления слева от краткой информации в карточках каталога
     и на главной. Если фото нет — просто не рендерим ничего, карточка
     выглядит как раньше (без плейсхолдера). --}}
@if ($photo)
    <div class="w-20 h-20 rounded-lg overflow-hidden bg-gray-100 shrink-0">
        <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->path) }}" alt="Фото объявления" class="w-full h-full object-cover">
    </div>
@endif
