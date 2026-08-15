<div>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <a href="{{ route('residential.search') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-800">&laquo; Назад в каталог</a>

        <div class="mt-4 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Фотогалерея -->
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    @if ($listing->photos->isNotEmpty())
                        <div class="grid grid-cols-2 gap-1">
                            @foreach ($listing->photos as $photo)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->path) }}" alt="Фото объявления" class="w-full h-48 object-cover">
                            @endforeach
                        </div>
                    @else
                        <div class="h-64 flex items-center justify-center bg-gray-100 text-gray-400 text-sm">
                            Фотографии ещё не добавлены
                        </div>
                    @endif
                </div>

                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="text-2xl font-bold">{{ number_format($listing->price, 0, '', ' ') }} ₽</div>
                            <div class="text-gray-600 mt-1">{{ $listing->address }}</div>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            {{ $listing->deal_type_label }}
                        </span>
                    </div>

                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 text-sm">
                        <div>
                            <dt class="text-gray-500">Тип</dt>
                            <dd class="font-medium">{{ $listing->property_type_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Площадь</dt>
                            <dd class="font-medium">{{ $listing->area }} м²</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Этаж</dt>
                            <dd class="font-medium">{{ $listing->floor }} / {{ $listing->total_floors }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Просмотры</dt>
                            <dd class="font-medium">{{ $listing->views_count }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6">
                        <h2 class="text-lg font-semibold mb-2">Описание</h2>
                        <p class="text-gray-700 whitespace-pre-line">{{ $listing->description }}</p>
                    </div>
                </div>

                <!-- Карта -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">Расположение на карте</h2>
                    <x-yandex-map :pins="$pin" />
                </div>
            </div>

            <!-- Продавец -->
            <div>
                <div class="bg-white rounded-xl shadow p-6 sticky top-6">
                    <h2 class="text-lg font-semibold mb-4">Продавец</h2>
                    <div class="font-medium">{{ $listing->user->full_name }}</div>
                    <div class="text-sm text-gray-500">На сайте с {{ $listing->user->created_at->format('m.Y') }}</div>

                    <button type="button" disabled
                            title="Чат с продавцом появится в следующем эпике дорожной карты"
                            class="mt-4 w-full inline-flex justify-center items-center px-4 py-2 bg-gray-300 text-gray-500 rounded-md font-semibold text-xs uppercase tracking-widest cursor-not-allowed">
                        Написать продавцу
                    </button>
                    <p class="mt-2 text-xs text-gray-400">Функция чата появится позже (эпик 10 дорожной карты).</p>
                </div>
            </div>
        </div>
    </div>
</div>
