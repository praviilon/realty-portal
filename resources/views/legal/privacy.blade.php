<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Политика конфиденциальности') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm rounded-lg p-4 mb-6">
                Черновой шаблон для MVP — перед реальным запуском его должен проверить юрист
                (в частности, на соответствие 152-ФЗ «О персональных данных»).
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6 sm:p-8 space-y-5 text-sm text-gray-700 leading-relaxed">
                <section>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">1. Какие данные мы собираем</h3>
                    <p>
                        При регистрации мы собираем имя, фамилию, email и номер телефона. При размещении
                        объявления — адрес объекта, его характеристики и загруженные фотографии. При
                        общении в чатах — текст сообщений между пользователями.
                    </p>
                </section>
                <section>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">2. Как мы используем данные</h3>
                    <p>
                        Данные используются для работы личного кабинета, модерации объявлений, показа
                        объектов в каталоге и на карте, а также для уведомлений о статусе объявлений и
                        новых сообщениях в чатах.
                    </p>
                </section>
                <section>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">3. Передача третьим лицам</h3>
                    <p>
                        Мы не продаём и не передаём персональные данные третьим лицам, за исключением
                        случаев, предусмотренных законодательством РФ.
                    </p>
                </section>
                <section>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">4. Хранение и удаление</h3>
                    <p>
                        Данные хранятся до удаления учётной записи. Для удаления аккаунта и связанных
                        данных используйте раздел «Профиль» → «Удалить аккаунт», либо обратитесь в
                        поддержку.
                    </p>
                </section>
                <section>
                    <h3 class="text-base font-semibold text-gray-900 mb-2">5. Контакты</h3>
                    <p>
                        По вопросам обработки персональных данных пишите на
                        <a href="mailto:privacy@a-realty.example" class="text-blue-600 hover:underline">privacy@a-realty.example</a>.
                    </p>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
