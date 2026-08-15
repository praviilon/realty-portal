<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Пользовательское соглашение') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm rounded-lg p-4 mb-6">
                Черновой шаблон для MVP — перед реальным запуском его должен проверить юрист.
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6 sm:p-8 space-y-5 text-sm text-gray-700 leading-relaxed">
                <section>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">1. Общие положения</h3>
                    <p>
                        Используя {{ config('app.name') }} (далее — «Портал»), вы соглашаетесь с условиями
                        настоящего соглашения. Портал предоставляет площадку для размещения объявлений о
                        продаже и аренде жилой, коммерческой недвижимости и рабочих пространств.
                    </p>
                </section>
                <section>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">2. Регистрация и учётная запись</h3>
                    <p>
                        Для размещения объявлений и использования личного кабинета требуется регистрация.
                        Вы обязуетесь указывать достоверные имя, фамилию, email и телефон и несёте
                        ответственность за сохранность пароля от своей учётной записи.
                    </p>
                </section>
                <section>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">3. Размещение объявлений</h3>
                    <p>
                        Каждое объявление проходит модерацию перед публикацией. Администрация вправе
                        отклонить объявление с указанием причины, а также удалить объявление, нарушающее
                        законодательство или правила Портала.
                    </p>
                </section>
                <section>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">4. Ответственность</h3>
                    <p>
                        Портал не является стороной сделок между пользователями и не несёт ответственности
                        за достоверность информации в объявлениях, а также за содержание переписки в чатах
                        между пользователями.
                    </p>
                </section>
                <section>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">5. Изменение условий</h3>
                    <p>
                        Администрация вправе изменять условия настоящего соглашения. Актуальная версия
                        всегда доступна на этой странице.
                    </p>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
