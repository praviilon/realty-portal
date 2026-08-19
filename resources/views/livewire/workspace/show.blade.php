<div>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <a href="{{ route('workspace.search') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-800">&laquo; Назад в каталог рабочих пространств</a>

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
                            <div class="text-2xl font-bold">
                                от {{ number_format($listing->display_price ?? 0, 0, '', ' ') }} ₽
                            </div>
                            <div class="text-gray-600 mt-1">{{ $listing->address }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800">
                                {{ $listing->workspace_type_label }}
                            </span>
                            <livewire:favorites.button :favoritable="$listing" :key="'fav-workspace-show-'.$listing->id" />
                            <livewire:comparison.button :comparable="$listing" :key="'cmp-workspace-show-'.$listing->id" />
                        </div>
                    </div>

                    <!-- Цены по периодам (1:M — workspace_pricing) -->
                    <div class="mt-4 flex flex-wrap gap-3">
                        @foreach ($pricingSorted as $price)
                            <div class="px-3 py-2 rounded-lg bg-gray-50 border text-sm">
                                <span class="font-semibold">{{ number_format($price->price, 0, '', ' ') }} ₽</span>
                                / {{ $price->period_label }}
                            </div>
                        @endforeach
                    </div>

                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 text-sm">
                        <div>
                            <dt class="text-gray-500">Площадь</dt>
                            <dd class="font-medium">{{ $listing->area }} м²</dd>
                        </div>
                        @if ($listing->workspace_subtype_label)
                            <div>
                                <dt class="text-gray-500">Тип места</dt>
                                <dd class="font-medium">{{ $listing->workspace_subtype_label }}</dd>
                            </div>
                        @endif
                        @if ($listing->building_type)
                            <div>
                                <dt class="text-gray-500">Тип здания</dt>
                                <dd class="font-medium">{{ $listing->building_type_label }}</dd>
                            </div>
                        @endif
                        @if ($listing->floor)
                            <div>
                                <dt class="text-gray-500">Этаж</dt>
                                <dd class="font-medium">{{ $listing->floor }} / {{ $listing->total_floors }}</dd>
                            </div>
                        @endif
                        @if ($listing->entrance_type)
                            <div>
                                <dt class="text-gray-500">Вход</dt>
                                <dd class="font-medium">{{ $entranceTypeLabels[$listing->entrance_type] ?? $listing->entrance_type }}</dd>
                            </div>
                        @endif
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
                        @if ($listing->deposit)
                            <div>
                                <dt class="text-gray-500">Депозит</dt>
                                <dd class="font-medium">{{ number_format($listing->deposit, 0, '', ' ') }} ₽</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-gray-500">Коммунальные платежи</dt>
                            <dd class="font-medium">{{ $listing->utilities_included ? 'Включены' : 'Не включены' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Просмотры</dt>
                            <dd class="font-medium">{{ $listing->views_count }}</dd>
                        </div>
                    </dl>

                    @if (!empty($listing->access_time))
                        <div class="mt-4">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Время доступа</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($listing->access_time as $slot)
                                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                                        {{ $accessTimeTypeLabels[$slot['type']] ?? $slot['type'] }}
                                        @if (($slot['type'] ?? null) !== 'round_the_clock' && !empty($slot['time_from']))
                                            {{ $slot['time_from'] }}&ndash;{{ $slot['time_to'] }}
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Особенности помещения (floor_features) — array_intersect отсекает
                         значения, которых больше нет в Workspace::floorFeatureLabels()
                         (в частности, старое 'separate_entrance' у ранее созданных
                         объявлений — эта особенность убрана по просьбе пользователя,
                         так как дублирует характеристику "Вход" выше). --}}
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

                    @if (!empty($listing->amenities))
                        <div class="mt-4">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Удобства</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($listing->amenities as $amenity)
                                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                                        {{ $amenityLabels[$amenity] ?? $amenity }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (!empty($listing->extra_options))
                        <div class="mt-4">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Дополнительные услуги</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($listing->extra_options as $option)
                                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                                        {{ $extraOptionLabels[$option] ?? $option }}
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

            <!-- Владелец -->
            <div>
                <div class="bg-white rounded-xl shadow p-6 sticky top-6">
                    <div class="flex items-start justify-between gap-2 mb-4">
                        <h2 class="text-lg font-semibold">{{ $listing->owner_type === 'agent' ? 'Агент' : 'Владелец' }}</h2>

                        {{-- По просьбе пользователя: автор, просматривающий своё же
                             объявление по прямой ссылке (в том числе неактивное),
                             видит здесь, на месте кнопки "Написать", индикатор
                             статуса объявления — та же цветная "полоска", что и в
                             личном кабинете. --}}
                        @if (auth()->id() === $listing->user_id)
                            <x-listing-status-badge :status="$listing->status" class="shrink-0" />
                        @endif
                    </div>
                    <div class="font-medium">{{ $listing->user->full_name }}</div>
                    <div class="text-sm text-gray-500">На сайте с {{ $listing->user->created_at->format('m.Y') }}</div>
                    <div class="mt-2 text-xs inline-flex items-center px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                        {{ $listing->contact_type_label }}
                    </div>

                    @auth
                        @if (auth()->id() !== $listing->user_id)
                            <x-primary-button type="button" wire:click="startChat" class="mt-4 w-full justify-center">
                                Написать
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
