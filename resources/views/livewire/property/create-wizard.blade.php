<div>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">
            {{ $editing ? 'Редактирование объявления' : 'Новое объявление' }}
        </h1>
        <p class="text-gray-500 text-sm mb-6">Жилая недвижимость · шаг {{ $step }} из 5</p>

        <!-- Индикатор шагов -->
        <ol class="flex items-center w-full mb-8 text-sm">
            @foreach (['Основное', 'Адрес', 'Характеристики', 'Цена', 'Фотографии'] as $i => $label)
                <li class="flex-1 flex items-center {{ $i + 1 < 5 ? 'after:content-[\'\'] after:flex-1 after:h-0.5 after:mx-2 ' . ($step > $i + 1 ? 'after:bg-primary-600' : 'after:bg-gray-200') : '' }}">
                    <button type="button" wire:click="goToStep({{ $i + 1 }})"
                            @disabled($step < $i + 1)
                            class="flex items-center justify-center w-8 h-8 rounded-full shrink-0 {{ $step >= $i + 1 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                        {{ $i + 1 }}
                    </button>
                    <span class="ms-2 hidden sm:inline {{ $step === $i + 1 ? 'font-semibold text-gray-900' : 'text-gray-500' }}">{{ $label }}</span>
                </li>
            @endforeach
        </ol>

        <div class="bg-white rounded-xl shadow p-6">
            <!-- Шаг 1: Основное -->
            @if ($step === 1)
                <div class="space-y-4">
                    <div>
                        <x-input-label for="dealType" value="Тип сделки" />
                        {{-- .live — чтобы подпись поля "Кто сдаёт"/"Кто продаёт" ниже
                             обновлялась сразу при переключении типа сделки. --}}
                        <select wire:model.live="dealType" id="dealType" class="mt-1 rounded-lg border-gray-300 w-full">
                            @foreach ($dealTypeLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('dealType')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="propertyType" value="Тип недвижимости" />
                        <select wire:model="propertyType" id="propertyType" class="mt-1 rounded-lg border-gray-300 w-full">
                            @foreach ($propertyTypeLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('propertyType')" class="mt-2" />
                    </div>

                    {{-- Кто разместил объявление / способ связи — по аналогии с
                         рабочими пространствами (App\Livewire\Workspace\CreateWizard).
                         В отличие от рабочих пространств у жилой недвижимости есть
                         продажа, поэтому подпись поля зависит от dealType. --}}
                    <div>
                        <x-input-label for="ownerType" :value="$dealType === 'rent' ? 'Кто сдаёт' : 'Кто продаёт'" />
                        <select wire:model="ownerType" id="ownerType" class="mt-1 rounded-lg border-gray-300 w-full">
                            @foreach ($ownerTypeLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('ownerType')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="contactType" value="Как связываться" />
                        <select wire:model="contactType" id="contactType" class="mt-1 rounded-lg border-gray-300 w-full">
                            @foreach ($contactTypeLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('contactType')" class="mt-2" />
                    </div>
                </div>
            @endif

            <!-- Шаг 2: Адрес -->
            @if ($step === 2)
                <div class="space-y-4">
                    <div>
                        <x-input-label for="address" value="Адрес" />
                        {{-- ИЗМЕНЕНО (по просьбе пользователя): поле заполняется
                             автоматически по выбору адреса ниже (подсказки/карта),
                             вручную не редактируется — иначе пользователь мог
                             ввести произвольный текст без реальных координат и
                             застрять на шаге, не сумев вручную подобрать
                             широту/долготу. --}}
                        <x-text-input wire:model="address" id="address" type="text" readonly
                            class="mt-1 block w-full bg-gray-50 text-gray-500 cursor-not-allowed"
                            placeholder="Заполнится автоматически по выбору адреса ниже" />
                        <x-input-error :messages="$errors->get('address')" class="mt-2" />

                        {{-- Эпик 20 (Веха 2): подсказки адреса + карта для уточнения точки --}}
                        <x-address-picker :address="$address" :lat="$lat" :lng="$lng" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="lat" value="Широта" />
                            <x-text-input wire:model="lat" id="lat" type="number" step="any" readonly
                                class="mt-1 block w-full bg-gray-50 text-gray-500 cursor-not-allowed" placeholder="55.751244" />
                            <x-input-error :messages="$errors->get('lat')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="lng" value="Долгота" />
                            <x-text-input wire:model="lng" id="lng" type="number" step="any" readonly
                                class="mt-1 block w-full bg-gray-50 text-gray-500 cursor-not-allowed" placeholder="37.618423" />
                            <x-input-error :messages="$errors->get('lng')" class="mt-2" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-400">
                        Адрес, широта и долгота заполняются автоматически — выберите адрес из подсказок или кликните точку на карте ниже.
                    </p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="metroStation" value="Станция метро (необязательно)" />
                            <x-text-input wire:model="metroStation" id="metroStation" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('metroStation')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="metroDistanceMin" value="Минут пешком до метро (необязательно)" />
                            <x-text-input wire:model="metroDistanceMin" id="metroDistanceMin" type="number" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('metroDistanceMin')" class="mt-2" />
                        </div>
                    </div>
                </div>
            @endif

            <!-- Шаг 3: Характеристики -->
            @if ($step === 3)
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="area" value="Площадь, м²" />
                            <x-text-input wire:model="area" id="area" type="number" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('area')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="floor" value="Этаж" />
                            <x-text-input wire:model="floor" id="floor" type="number" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('floor')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="totalFloors" value="Этажей в доме" />
                            <x-text-input wire:model="totalFloors" id="totalFloors" type="number" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('totalFloors')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="heatingType" value="Отопление" />
                            <select wire:model="heatingType" id="heatingType" class="mt-1 rounded-lg border-gray-300 w-full">
                                @foreach ($heatingTypeLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('heatingType')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="finishingType" value="Отделка" />
                            <select wire:model="finishingType" id="finishingType" class="mt-1 rounded-lg border-gray-300 w-full">
                                @foreach ($finishingTypeLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('finishingType')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="furniture" value="Мебель" />
                            <select wire:model="furniture" id="furniture" class="mt-1 rounded-lg border-gray-300 w-full">
                                @foreach ($furnitureLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('furniture')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Особенности помещения" />
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            @foreach ($floorFeatureLabels as $value => $label)
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" wire:model="floorFeatures" value="{{ $value }}" class="rounded border-gray-300 text-primary-600">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('floorFeatures')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" value="Описание" />
                        <textarea wire:model="description" id="description" rows="5" class="mt-1 rounded-lg border-gray-300 w-full"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                </div>
            @endif

            <!-- Шаг 4: Цена и условия (по аналогии с коммерческой недвижимостью) -->
            @if ($step === 4)
                <div class="space-y-4">
                    @if ($dealType === 'rent')
                        <div>
                            <x-input-label for="pricePerMonth" value="Цена в месяц, ₽" />
                            <x-text-input wire:model="pricePerMonth" id="pricePerMonth" type="number" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('pricePerMonth')" class="mt-2" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="deposit" value="Депозит, ₽ (необязательно)" />
                                <x-text-input wire:model="deposit" id="deposit" type="number" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('deposit')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="commission" value="Комиссия, ₽ (необязательно)" />
                                <x-text-input wire:model="commission" id="commission" type="number" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('commission')" class="mt-2" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="rentType" value="Тип аренды" />
                            <select wire:model="rentType" id="rentType" class="mt-1 rounded-lg border-gray-300 w-full">
                                @foreach ($rentTypeLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model="utilitiesIncluded" class="rounded border-gray-300 text-primary-600">
                            Коммунальные платежи включены в стоимость
                        </label>
                    @else
                        <div>
                            <x-input-label for="price" value="Цена, ₽" />
                            <x-text-input wire:model="price" id="price" type="number" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('price')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="commission" value="Комиссия, ₽ (необязательно)" />
                            <x-text-input wire:model="commission" id="commission" type="number" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('commission')" class="mt-2" />
                        </div>
                    @endif
                </div>
            @endif

            <!-- Шаг 5: Фотографии -->
            @if ($step === 5)
                <div class="space-y-4">
                    <div>
                        <x-input-label for="newPhotos" :value="'Фотографии (необязательно, максимум ' . $maxPhotos . ')'" />
                        <p class="text-xs text-gray-400 mb-1">Осталось слотов: {{ $photoSlotsRemaining }} из {{ $maxPhotos }}</p>
                        <x-photo-dropzone class="mt-1" :remaining="$photoSlotsRemaining" />
                        <x-input-error :messages="$errors->get('incomingPhotos.*')" class="mt-2" />
                        <x-input-error :messages="$errors->get('newPhotos')" class="mt-2" />
                        <x-input-error :messages="$errors->get('newPhotos.*')" class="mt-2" />
                        <div wire:loading wire:target="incomingPhotos" class="text-xs text-gray-400 mt-1">Загрузка...</div>
                    </div>

                    @if (count($newPhotos))
                        <div>
                            <p class="text-sm text-gray-500 mb-2">Новые фото (ещё не сохранены):</p>
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                                @foreach ($newPhotos as $index => $photo)
                                    <div class="relative">
                                        <img src="{{ $photo->temporaryUrl() }}" class="w-full h-24 object-cover rounded-lg">
                                        <button type="button" wire:click="removeNewPhoto({{ $index }})"
                                                class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-6 h-6 text-xs">&times;</button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($editing && $editing->photos->isNotEmpty())
                        <div>
                            <p class="text-sm text-gray-500 mb-2">Уже загруженные фото:</p>
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                                {{-- ИЗМЕНЕНО (по просьбе пользователя): раньше у уже
                                     загруженных фото не было крестика — удалить их
                                     было невозможно. --}}
                                @foreach ($editing->photos as $photo)
                                    <div class="relative">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->path) }}" class="w-full h-24 object-cover rounded-lg">
                                        <button type="button" wire:click="removeExistingPhoto({{ $photo->id }})"
                                                wire:confirm="Удалить это фото? Действие необратимо."
                                                class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-6 h-6 text-xs">&times;</button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Навигация -->
            <div class="flex items-center justify-between mt-8 pt-4 border-t">
                <x-secondary-button type="button" wire:click="previousStep" :disabled="$step === 1">
                    Назад
                </x-secondary-button>

                @if ($step < 5)
                    <x-primary-button type="button" wire:click="nextStep">
                        Далее
                    </x-primary-button>
                @else
                    <x-primary-button type="button" wire:click="submit">
                        Отправить на модерацию
                    </x-primary-button>
                @endif
            </div>
        </div>
    </div>
</div>
