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
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800">
                                {{ $listing->deal_type_label }}
                            </span>
                            <livewire:favorites.button :favoritable="$listing" :key="'fav-residential-show-'.$listing->id" />
                            <livewire:comparison.button :comparable="$listing" :key="'cmp-residential-show-'.$listing->id" />
                        </div>
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
                        @if ($listing->metro_station)
                            <div>
                                <dt class="text-gray-500">Метро</dt>
                                <dd class="font-medium">
                                    {{ $listing->metro_station }}
                                    @if ($listing->metro_distance_min)
                                        · {{ $listing->metro_distance_min }} мин пешком
                                    @endif
                                </dd>
                            </div>
                        @endif
                        @if ($listing->heating_type)
                            <div>
                                <dt class="text-gray-500">Отопление</dt>
                                <dd class="font-medium">{{ $heatingTypeLabels[$listing->heating_type] ?? $listing->heating_type }}</dd>
                            </div>
                        @endif
                        @if ($listing->finishing_type)
                            <div>
                                <dt class="text-gray-500">Отделка</dt>
                                <dd class="font-medium">{{ $finishingTypeLabels[$listing->finishing_type] ?? $listing->finishing_type }}</dd>
                            </div>
                        @endif
                        @if ($listing->furniture)
                            <div>
                                <dt class="text-gray-500">Мебель</dt>
                                <dd class="font-medium">{{ $furnitureLabels[$listing->furniture] ?? $listing->furniture }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-gray-500">Просмотры</dt>
                            <dd class="font-medium">{{ $listing->views_count }}</dd>
                        </div>
                    </dl>

                    {{-- Особенности помещения (floor_features) — array_intersect отсекает
                         значения, которых больше нет в ResidentialProperty::floorFeatureLabels()
                         (см. аналогичную защиту на странице рабочего пространства). --}}
                    @php($visibleFloorFeatures = array_intersect($listing->floor_features ?? [], array_keys($floorFeatureLabels)))
                    @if (!empty($visibleFloorFeatures))
                        <div class="mt-4">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Особенности помещения</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($visibleFloorFeatures as $feature)
                                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                                        {{ $floorFeatureLabels[$feature] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

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

                    @auth
                        @if (auth()->id() !== $listing->user_id)
                            <x-primary-button type="button" wire:click="startChat" class="mt-4 w-full justify-center">
                                Написать продавцу
                            </x-primary-button>
                        @endif
                    @else
                        <a href="{{ route('login') }}" wire:navigate class="mt-4 block text-center w-full px-4 py-2 bg-gray-800 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-700">
                            Войти, чтобы написать
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</div>
