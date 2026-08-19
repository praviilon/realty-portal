<x-filament-panels::page>
    <div class="space-y-6">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Реестр внешних сервисов, с которыми взаимодействует портал. Собственного публичного API у портала
            нет, поэтому это не Swagger/OpenAPI-документация, а список исходящих интеграций: что подключено,
            зачем используется, настроено ли сейчас (по переменным окружения) и где почитать документацию
            провайдера.
        </p>

        <p class="text-sm text-gray-600 dark:text-gray-400">
            Текущий активный драйвер отправки почты: <strong>{{ $this->getCurrentMailDriver() }}</strong>
            @if ($this->getCurrentMailDriver() === 'log')
                (письма не отправляются по-настоящему, попадают в лог приложения)
            @endif
        </p>

        @foreach ($this->getIntegrations()->groupBy('category') as $category => $items)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-800 px-4 py-2 font-semibold text-sm">
                    {{ $category }}
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($items as $integration)
                        <div class="p-4 space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">{{ $integration['name'] }}</span>

                                @if ($integration['configured'])
                                    <x-filament::badge color="success">Настроено</x-filament::badge>
                                @else
                                    <x-filament::badge color="danger">Не настроено</x-filament::badge>
                                @endif

                                @if (! $integration['wired_in_code'])
                                    <x-filament::badge color="gray">Не используется в коде</x-filament::badge>
                                @endif

                                @if (! is_null($integration['is_active_mail_driver']))
                                    @if ($integration['is_active_mail_driver'])
                                        <x-filament::badge color="info">Активный драйвер почты</x-filament::badge>
                                    @else
                                        <x-filament::badge color="gray">Не активен (не выбран как MAIL_MAILER)</x-filament::badge>
                                    @endif
                                @endif
                            </div>

                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $integration['purpose'] }}
                            </p>

                            <div class="text-xs text-gray-500 dark:text-gray-500 flex flex-wrap items-center gap-x-4 gap-y-1">
                                <span>
                                    Ключи: {{ implode(', ', $integration['config_keys']) }}
                                </span>

                                <a
                                    href="{{ $integration['docs_url'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-primary-600 hover:underline dark:text-primary-400"
                                >
                                    Документация провайдера &rarr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
