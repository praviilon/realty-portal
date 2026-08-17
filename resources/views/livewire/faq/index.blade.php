<div>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Часто задаваемые вопросы</h1>

        <div class="flex flex-wrap gap-2 mb-8">
            <button type="button" wire:click="setCategory('')"
                    class="px-4 py-2 rounded-lg text-sm border {{ $category === '' ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-200' }}">
                Все категории
            </button>
            @foreach ($categories as $cat)
                <button type="button" wire:click="setCategory('{{ $cat }}')"
                        class="px-4 py-2 rounded-lg text-sm border {{ $category === $cat ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-200' }}">
                    {{ $cat }}
                </button>
            @endforeach
        </div>

        @if ($faqsByCategory->isEmpty())
            <p class="text-gray-500">Вопросов в этой категории пока нет.</p>
        @else
            @foreach ($faqsByCategory as $categoryName => $faqs)
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mt-6 mb-2">{{ $categoryName }}</h3>

                <div id="faq-accordion-{{ Str::slug($categoryName) }}" data-accordion="collapse">
                    @foreach ($faqs as $faq)
                        <h4 id="faq-page-heading-{{ $faq->id }}">
                            <button type="button" class="flex items-center justify-between w-full py-4 font-medium text-left text-gray-700 border-b border-gray-200"
                                    data-accordion-target="#faq-page-body-{{ $faq->id }}" aria-expanded="false" aria-controls="faq-page-body-{{ $faq->id }}">
                                <span>{{ $faq->question }}</span>
                                <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                                </svg>
                            </button>
                        </h4>
                        <div id="faq-page-body-{{ $faq->id }}" class="hidden" aria-labelledby="faq-page-heading-{{ $faq->id }}">
                            <p class="py-3 text-gray-600">{{ $faq->answer }}</p>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @endif
    </div>
</div>
