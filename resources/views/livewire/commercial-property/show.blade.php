<div>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <a href="{{ route('commercial.search') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-800">&laquo; Назад в каталог коммерческой недвижимости</a>

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
                                {{ number_format($listing->display_price ?? 0, 0, '', ' ') }} ₽{{ $listing->deal_type === 'rent' ? '/мес.' : '' }}
                            </div>
                            <div class="text-gray-600 mt-1">{{ $listing->address }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800">
                                {{ $listing->deal_type_label }}
                            </span>
                            <livewire:favorites.button :favoritable="$listing" :key="'fav-commercial-show-'.$listing->id" />
                            <livewire:comparison.button :comparable="$listing" :key="'cmp-commercial-show-'.$listing->id" />
                        </div>
                    </div>

                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 text-sm">
                        <div>
                            <dt class="text-gray-500">Назначение</dt>
                            <dd class="font-medium">{{ $listing->purpose_type_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Тип здания</dt>
                            <dd class="font-medium">{{ $listing->building_type_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Площадь</dt>
                            <dd class="font-medium">{{ $listing->area }} м²</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Этаж</dt>
                            <dd class="font-medium">{{ $listing->floor }} / {{ $listing->total_floors }}</dd>
                        </div>
                        @if ($listing->ceiling_height)
                            <div>
                                <dt class="text-gray-500">Высота потолков</dt>
                                <dd class="font-medium">{{ $listing->ceiling_height }} м</dd>
                            </div>
                        @endif
                        @if ($listing->entrance_type)
                            <div>
                                <dt class="text-gray-500">Вход</dt>
                                <dd class="font-medium">{{ $entranceTypeLabels[$listing->entrance_type] ?? $listing->entrance_type }}</dd>
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

                    @if (!empty($listing->floor_features))
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($listing->floor_features as $feature)
                                <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                                    {{ $floorFeatureLabels[$feature] ?? $feature }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if ($listing->deal_type === 'rent' && $listing->rentDetail)
                        <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm border-t pt-4">
                            <div>
                                <dt class="text-gray-500">Тип аренды</dt>
                                <dd class="font-medium">{{ $rentTypeLabels[$listing->rentDetail->rent_type] ?? $listing->rentDetail->rent_type }}</dd>
                            </div>
                            @if ($listing->rentDetail->deposit)
                                <div>
                                    <dt class="text-gray-500">Депозит</dt>
                                    <dd class="font-medium">{{ number_format($listing->rentDetail->deposit, 0, '', ' ') }} ₽</dd>
                                </div>
                            @endif
                            @if ($listing->rentDetail->commission)
                                <div>
                                    <dt class="text-gray-500">Комиссия</dt>
                                    <dd class="font-medium">{{ number_format($listing->rentDetail->commission, 0, '', ' ') }} ₽</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-gray-500">Коммунальные платежи</dt>
                                <dd class="font-medium">{{ $listing->rentDetail->utilities_included ? 'Включены' : 'Не включены' }}</dd>
                            </div>
                        </div>
                    @elseif ($listing->deal_type === 'sale' && $listing->saleDetail && $listing->saleDetail->commission)
                        <div class="mt-6 grid grid-cols-2 gap-4 text-sm border-t pt-4">
                            <div>
                                <dt class="text-gray-500">Комиссия</dt>
                                <dd class="font-medium">{{ number_format($listing->saleDetail->commission, 0, '', ' ') }} ₽</dd>
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
