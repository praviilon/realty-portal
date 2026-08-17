<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Помощь') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow sm:rounded-lg p-6 sm:p-8 space-y-6 text-sm text-gray-700 leading-relaxed">
                <section>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Как разместить объявление</h3>
                    <p>
                        Зарегистрируйтесь или войдите, откройте «Личный кабинет» → «Разместить объявление»
                        и заполните пошаговую форму: тип сделки, адрес, характеристики и фотографии.
                        После отправки объявление уходит на модерацию и появляется в каталоге после
                        проверки администратором.
                    </p>
                </section>
                <section>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Почему объявление не публикуется сразу</h3>
                    <p>
                        Все объявления проверяются модератором перед публикацией — обычно в течение
                        одного рабочего дня. Если объявление отклонено, причина будет видна в личном
                        кабинете рядом с объявлением.
                    </p>
                </section>
                <section>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Как связаться с продавцом</h3>
                    <p>
                        На странице объявления нажмите «Написать продавцу» — откроется чат, где можно
                        обсудить детали напрямую. Все ваши диалоги собраны в разделе «Сообщения».
                    </p>
                </section>
                <section>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">Забыли пароль</h3>
                    <p>
                        Самостоятельное восстановление пароля пока не реализовано — напишите на
                        <a href="mailto:support@a-realty.example" class="text-primary-600 hover:underline">support@a-realty.example</a>,
                        и администратор сбросит пароль вручную.
                    </p>
                </section>
            </div>

            <div class="text-center">
                <a href="{{ route('faq.index') }}" wire:navigate class="text-primary-600 hover:underline text-sm">
                    Смотреть часто задаваемые вопросы &rarr;
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
