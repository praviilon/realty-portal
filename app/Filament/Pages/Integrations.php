<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * Реестр внешних сервисов ("API"), подключённых к порталу — по просьбе
 * пользователя ("собрать swagger или что-то аналогичное со всеми API
 * которые подключены к порталу... отдельной страницей в админ панели").
 *
 * У портала нет собственного публичного API, поэтому это не Swagger/OpenAPI
 * (документировать нечего — своих эндпоинтов со схемами запроса/ответа не
 * существует), а простой реестр исходящих интеграций: что подключено,
 * зачем, настроено ли сейчас и где документация. Список описан в
 * config/integrations.php — там же подробно объяснено, почему выбран
 * именно такой формат.
 */
class Integrations extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'Интеграции';

    protected static ?string $title = 'Интеграции';

    protected static string $view = 'filament.pages.integrations';

    /**
     * @return Collection<int, array{
     *     key: string,
     *     name: string,
     *     category: string,
     *     purpose: string,
     *     config_keys: array<int, string>,
     *     docs_url: string,
     *     wired_in_code: bool,
     *     configured: bool,
     *     is_active_mail_driver: bool|null,
     * }>
     */
    public function getIntegrations(): Collection
    {
        return collect(config('integrations', []))->map(function (array $integration) {
            $configured = collect($integration['config_keys'])
                ->every(fn (string $configKey) => filled(config($configKey)));

            $integration['configured'] = $configured;

            $integration['is_active_mail_driver'] = isset($integration['mail_driver'])
                ? config('mail.default') === $integration['mail_driver']
                : null;

            return $integration;
        });
    }

    public function getCurrentMailDriver(): string
    {
        return (string) config('mail.default');
    }
}
