<div>
    <!-- Hero + форма поиска -->
    <div class="bg-gray-800">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
            <h1 class="text-3xl sm:text-4xl font-bold text-white">Найдите недвижимость мечты</h1>
            <p class="mt-3 text-gray-300">Продажа и аренда квартир, домов и коммерческих помещений</p>

            <form wire:submit="search" class="mt-8 bg-white rounded-xl shadow-lg p-4 flex flex-wrap gap-4 items-end justify-center">
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
                <div>
                    <x-primary-button type="submit">Найти</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <!-- Подборка объявлений -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900">Новые объявления</h2>
            <a href="{{ route('residential.search') }}" wire:navigate class="text-sm text-blue-600 hover:underline">Смотреть все &rarr;</a>
        </div>

        @if ($featured->isEmpty())
            <p class="text-gray-500">Пока нет активных объявлений — станьте первым, кто разместит объект.</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($featured as $listing)
                    <a href="{{ route('residential.show', $listing) }}" wire:navigate class="block bg-white border rounded-xl p-4 hover:shadow-lg transition">
                        <div class="flex items-center justify-between">
                            <div class="font-semibold text-lg">{{ number_format($listing->price, 0, '', ' ') }} ₽</div>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $listing->property_type_label }}</span>
                        </div>
                        <div class="text-gray-600 text-sm mt-1">{{ $listing->address }}</div>
                        <div class="text-gray-500 text-sm">{{ $listing->area }} м² · этаж {{ $listing->floor }}/{{ $listing->total_floors }}</div>
                    </a>
                @endforeach
            </div>
        @endif
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
