<div>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Сравнение объявлений</h1>

        <div class="flex flex-wrap gap-2 mb-6">
            @foreach (\App\Livewire\Comparison\Index::TABS as $type => $label)
                <button type="button" wire:click="setTab('{{ $type }}')"
                        class="px-4 py-2 rounded-lg text-sm border {{ $tab === $type ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-200' }}">
                    {{ $label }} ({{ $counts[$type] ?? 0 }})
                </button>
            @endforeach
        </div>

        @if ($items->isEmpty())
            <p class="text-gray-500">
                В сравнении по вкладке «{{ \App\Livewire\Comparison\Index::TABS[$tab] }}» пока ничего нет.
                Добавляйте объявления кнопкой сравнения в каталоге или на карточке объявления (максимум 3 объекта).
            </p>
        @else
            <div class="flex items-center justify-end mb-3">
                <button type="button" wire:click="clearList" class="text-sm text-gray-500 hover:text-red-600 underline">
                    Очистить сравнение
                </button>
            </div>

            <div class="overflow-x-auto bg-white rounded-xl shadow">
                <table class="min-w-full text-sm">
                    <tbody>
                        <tr class="border-b">
                            <td class="p-4 font-medium text-gray-500 w-40">Объявление</td>
                            @foreach ($items as $item)
                                @php($listing = $item->comparable)
                                @php($showRoute = match ($category) { 'residential' => route('residential.show', $listing), 'commercial' => route('commercial.show', $listing), default => route('workspace.show', $listing) })
                                <td class="p-4 align-top">
                                    <a href="{{ $showRoute }}"
                                       wire:navigate class="font-medium text-blue-600 hover:underline">
                                        {{ $listing->address }}
                                    </a>
                                    <div>
                                        <button type="button" wire:click="removeItem({{ $item->id }})" class="mt-2 text-xs text-gray-400 hover:text-red-600">
                                            &times; Убрать
                                        </button>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                        <tr class="border-b bg-gray-50">
                            <td class="p-4 font-medium text-gray-500">Цена</td>
                            @foreach ($items as $item)
                                @php($listing = $item->comparable)
                                @php($price = match ($category) { 'residential' => $listing->price, default => $listing->display_price })
                                <td class="p-4 font-semibold">
                                    {{ $category === 'workspace' ? 'от ' : '' }}{{ number_format($price ?? 0, 0, '', ' ') }} ₽{{ str_ends_with($tab, 'rent') ? '/мес.' : '' }}
                                </td>
                            @endforeach
                        </tr>
                        <tr class="border-b">
                            <td class="p-4 font-medium text-gray-500">Тип</td>
                            @foreach ($items as $item)
                                @php($listing = $item->comparable)
                                @php($typeLabel = match ($category) { 'residential' => $listing->property_type_label, 'commercial' => $listing->purpose_type_label, default => $listing->workspace_type_label })
                                <td class="p-4">{{ $typeLabel }}</td>
                            @endforeach
                        </tr>
                        <tr class="border-b bg-gray-50">
                            <td class="p-4 font-medium text-gray-500">Площадь</td>
                            @foreach ($items as $item)
                                <td class="p-4">{{ $item->comparable->area }} м²</td>
                            @endforeach
                        </tr>
                        @if ($category !== 'workspace')
                            <tr class="border-b">
                                <td class="p-4 font-medium text-gray-500">Этаж</td>
                                @foreach ($items as $item)
                                    <td class="p-4">{{ $item->comparable->floor }} / {{ $item->comparable->total_floors }}</td>
                                @endforeach
                            </tr>
                        @endif
                        <tr>
                            <td class="p-4 font-medium text-gray-500">Просмотры</td>
                            @foreach ($items as $item)
                                <td class="p-4">{{ $item->comparable->views_count }}</td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
