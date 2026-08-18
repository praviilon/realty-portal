<div>
    <!-- Hero + форма поиска (эпик 29, Веха 3: вкладки по типам объявлений) -->
    <div class="bg-gray-800">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
            <h1 class="text-3xl sm:text-4xl font-bold text-white">Найдите недвижимость мечты</h1>
            <p class="mt-3 text-gray-300">Жильё, коммерция и рабочие пространства — продажа и аренда</p>

            {{-- ИСПРАВЛЕНО (по просьбе пользователя): вкладки и белая область
                 фильтров раньше были соседними элементами со своей
                 собственной shrink-to-fit шириной каждый — при разной ширине
                 контента (короткие подписи вкладок против более широкой формы
                 с полями) они не совпадали по ширине, особенно на широких
                 экранах. Оборачиваем оба блока в один inline-grid без
                 явных grid-template-columns: единственная колонка авто-
                 подстраивается под САМЫЙ широкий из двух элементов (стандартный
                 CSS grid приём), и оба (justify-items: stretch по умолчанию)
                 растягиваются на одинаковую итоговую ширину. Верхний правый
                 угол формы больше не скруглён (rounded-tr-xl убран) — теперь
                 верхний край формы целиком примыкает к вкладкам той же
                 ширины, скруглённые углы там были не нужны. --}}
            <div class="mt-8 inline-grid text-sm">
                <div class="flex rounded-t-lg overflow-hidden">
                    <button type="button" wire:click="switchCategory('residential')"
                            class="flex-1 whitespace-nowrap px-5 py-2.5 font-medium {{ $activeCategory === 'residential' ? 'bg-white text-gray-900' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                        Жильё
                    </button>
                    <button type="button" wire:click="switchCategory('commercial')"
                            class="flex-1 whitespace-nowrap px-5 py-2.5 font-medium {{ $activeCategory === 'commercial' ? 'bg-white text-gray-900' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                        Коммерция
                    </button>
                    <button type="button" wire:click="switchCategory('workspace')"
                            class="flex-1 whitespace-nowrap px-5 py-2.5 font-medium {{ $activeCategory === 'workspace' ? 'bg-white text-gray-900' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                        Рабочие пространства
                    </button>
                </div>

            <form wire:submit="search" class="bg-white rounded-b-xl shadow-lg p-4 flex flex-wrap gap-4 items-end justify-center">
                @if ($activeCategory === 'residential')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 text-left">Сделка</label>
                        <select wire:model="searchDealType" class="rounded-lg border-gray-300 text-sm">
                            <option value="sale">Купить</option>
                            <option value="rent">Снять</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 text-left">Тип недвижимости</label>
                        <select wire:model="searchPropertyType" class="rounded-lg border-gray-300 text-sm">
                            <option value="">Любой</option>
                            @foreach ($propertyTypeLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif ($activeCategory === 'commercial')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 text-left">Сделка</label>
                        <select wire:model="searchCommercialDealType" class="rounded-lg border-gray-300 text-sm">
                            <option value="sale">Купить</option>
                            <option value="rent">Снять</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 text-left">Назначение</label>
                        <select wire:model="searchPurposeType" class="rounded-lg border-gray-300 text-sm">
                            <option value="">Любое</option>
                            @foreach ($purposeTypeLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 text-left">Тип пространства</label>
                        <select wire:model="searchWorkspaceType" class="rounded-lg border-gray-300 text-sm">
                            <option value="">Любой</option>
                            @foreach ($workspaceTypeLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <x-primary-button type="submit">Найти</x-primary-button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- Подборки объявлений — три группы по типам (доработка после Вехи 3) -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-12">
        <div>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">Жилая недвижимость</h2>
                <a href="{{ route('residential.search') }}" wire:navigate class="text-sm text-primary-600 hover:underline">Смотреть все &rarr;</a>
            </div>

            @if ($featuredResidential->isEmpty())
                <p class="text-gray-500">Пока нет активных объявлений — станьте первым, кто разместит объект.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($featuredResidential as $listing)
                        <a href="{{ route('residential.show', $listing) }}" wire:navigate class="flex gap-3 bg-white border rounded-xl p-4 hover:shadow-lg transition">
                            <x-listing-thumb :photo="$listing->mainPhoto" />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <div class="font-semibold text-lg">{{ number_format($listing->price, 0, '', ' ') }} ₽</div>
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $listing->property_type_label }}</span>
                                </div>
                                <div class="text-gray-600 text-sm mt-1">{{ $listing->address }}</div>
                                <div class="text-gray-500 text-sm">{{ $listing->area }} м² · этаж {{ $listing->floor }}/{{ $listing->total_floors }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">Коммерческая недвижимость</h2>
                <a href="{{ route('commercial.search') }}" wire:navigate class="text-sm text-primary-600 hover:underline">Смотреть все &rarr;</a>
            </div>

            @if ($featuredCommercial->isEmpty())
                <p class="text-gray-500">Пока нет активных объявлений в этой категории.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($featuredCommercial as $listing)
                        <a href="{{ route('commercial.show', $listing) }}" wire:navigate class="flex gap-3 bg-white border rounded-xl p-4 hover:shadow-lg transition">
                            <x-listing-thumb :photo="$listing->mainPhoto" />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <div class="font-semibold text-lg">
                                        {{ number_format($listing->display_price ?? 0, 0, '', ' ') }} ₽{{ $listing->deal_type === 'rent' ? '/мес.' : '' }}
                                    </div>
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $listing->purpose_type_label }}</span>
                                </div>
                                <div class="text-gray-600 text-sm mt-1">{{ $listing->address }}</div>
                                <div class="text-gray-500 text-sm">{{ $listing->area }} м² · этаж {{ $listing->floor }}/{{ $listing->total_floors }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">Рабочие пространства</h2>
                <a href="{{ route('workspace.search') }}" wire:navigate class="text-sm text-primary-600 hover:underline">Смотреть все &rarr;</a>
            </div>

            @if ($featuredWorkspaces->isEmpty())
                <p class="text-gray-500">Пока нет активных объявлений в этой категории.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($featuredWorkspaces as $listing)
                        <a href="{{ route('workspace.show', $listing) }}" wire:navigate class="flex gap-3 bg-white border rounded-xl p-4 hover:shadow-lg transition">
                            <x-listing-thumb :photo="$listing->mainPhoto" />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <div class="font-semibold text-lg">от {{ number_format($listing->display_price ?? 0, 0, '', ' ') }} ₽</div>
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $listing->workspace_type_label }}</span>
                                </div>
                                <div class="text-gray-600 text-sm mt-1">{{ $listing->address }}</div>
                                <div class="text-gray-500 text-sm">{{ $listing->area }} м²</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- FAQ -->
    @if ($faqsByCategory->isNotEmpty())
        <div id="faq" class="bg-white border-t border-gray-100">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Часто задаваемые вопросы</h2>

                @foreach ($faqsByCategory as $category => $faqs)
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mt-6 mb-2">{{ $category }}</h3>

                    <div id="faq-accordion-{{ Str::slug($category) }}" data-accordion="collapse">
                        @foreach ($faqs as $faq)
                            <h4 id="faq-heading-{{ $faq->id }}">
                                <button type="button" class="flex items-center justify-between w-full py-4 font-medium text-left text-gray-700 border-b border-gray-200"
                                        data-accordion-target="#faq-body-{{ $faq->id }}" aria-expanded="false" aria-controls="faq-body-{{ $faq->id }}">
                                    <span>{{ $faq->question }}</span>
                                    <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 10 6">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                                    </svg>
                                </button>
                            </h4>
                            <div id="faq-body-{{ $faq->id }}" class="hidden" aria-labelledby="faq-heading-{{ $faq->id }}">
                                <p class="py-3 text-gray-600">{{ $faq->answer }}</p>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
