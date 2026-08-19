@props(['status'])

{{--
    Цветная "полоска"-индикатор статуса объявления — переиспользуемая версия
    бейджика, который раньше был продублирован в трёх списках личного
    кабинета (resources/views/dashboard.blade.php). По просьбе пользователя
    теперь тот же самый индикатор используется и на страницах объекта — в
    карточке автора, когда сам автор смотрит своё объявление по прямой
    ссылке (см. resources/views/livewire/{property,commercial-property,workspace}/show.blade.php).
--}}
@php
    $labels = [
        'moderation' => 'На модерации',
        'active' => 'Активно',
        'rejected' => 'Отклонено',
        'archived' => 'В архиве',
    ];
@endphp

<span {{ $attributes->class([
    'text-xs font-medium px-2 py-1 rounded-full',
    'bg-green-100 text-green-800' => $status === 'active',
    'bg-yellow-100 text-yellow-800' => $status === 'moderation',
    'bg-red-100 text-red-800' => $status === 'rejected',
    'bg-gray-100 text-gray-600' => $status === 'archived',
]) }}>
    {{ $labels[$status] ?? $status }}
</span>
