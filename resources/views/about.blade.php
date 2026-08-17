<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('О компании') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6 sm:p-8 space-y-4 text-sm text-gray-700 leading-relaxed">
                <p>
                    {{ config('app.name') }} — портал объявлений о недвижимости: жильё, коммерческие
                    помещения и рабочие пространства в одном каталоге. Мы помогаем собственникам и
                    агентам быстро находить покупателей и арендаторов, а пользователям — удобно
                    искать подходящий объект с фильтрами, картой и личным кабинетом.
                </p>
                <p>
                    Каждое объявление проходит модерацию перед публикацией, чтобы каталог оставался
                    актуальным и без дублей.
                </p>
                <h3 class="text-base font-semibold text-gray-900 pt-2">Контакты</h3>
                <p>
                    Электронная почта поддержки: <a href="mailto:support@a-realty.example" class="text-primary-600 hover:underline">support@a-realty.example</a><br>
                    По вопросам размещения объявлений и работе личного кабинета — раздел «Помощь».
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
