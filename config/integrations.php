<?php

/**
 * Реестр внешних сервисов, с которыми взаимодействует портал — по просьбе
 * пользователя ("собрать swagger или что-то аналогичное со всеми API
 * которые подключены к порталу... отдельной страницей в админ панели").
 *
 * У портала нет собственного публичного REST/JSON API (routes/api.php
 * пуст), поэтому классический Swagger/OpenAPI документировать нечего —
 * вместо этого здесь реестр исходящих интеграций: какие внешние сервисы
 * подключены, зачем, через какие переменные окружения настраиваются и где
 * почитать их документацию. Отображается на странице
 * App\Filament\Pages\Integrations (App /admin/integrations).
 *
 * Поле 'config_keys' — пути в config(), по которым реальный код читает
 * значения (те же самые, что используются в App\Services\SmartCaptchaVerifier
 * и resources/views/components/{yandex-map,smart-captcha}.blade.php), а не
 * env() напрямую — так проверка "настроено ли" отражает то, что видит
 * приложение в рантайме (и это же позволяет тестировать страницу через
 * config(), не трогая реальный .env).
 *
 * Поле 'wired_in_code' — честно показывает, реально ли что-то в коде
 * вызывает эту интеграцию, а не просто существует ли под неё заготовка
 * конфига (пример — Slack: ключ в config/services.php есть исторически от
 * скелета Laravel, но ни один канал уведомлений в коде его не использует).
 */

return [

    [
        'key' => 'yandex_maps',
        'name' => 'Yandex Maps JS API',
        'category' => 'Карты',
        'purpose' => 'Отображение карты и меток объявлений на страницах объекта, в каталоге и в шаге выбора адреса (клиентский JS-виджет в браузере пользователя).',
        'config_keys' => ['services.yandex_maps.api_key'],
        'docs_url' => 'https://yandex.ru/dev/jsapi30/doc/ru/',
        'wired_in_code' => true,
    ],
    [
        'key' => 'smartcaptcha',
        'name' => 'Yandex SmartCaptcha',
        'category' => 'Защита от ботов',
        'purpose' => 'Виджет капчи в формах регистрации и входа (клиентский ключ) плюс серверная проверка токена запросом к smartcaptcha.yandexcloud.net (App\\Services\\SmartCaptchaVerifier).',
        'config_keys' => ['services.smartcaptcha.site_key', 'services.smartcaptcha.server_key'],
        'docs_url' => 'https://cloud.yandex.ru/docs/smartcaptcha/',
        'wired_in_code' => true,
    ],
    [
        'key' => 'mail_postmark',
        'name' => 'Postmark',
        'category' => 'Почта',
        'purpose' => 'Драйвер отправки почты Laravel — используется только если MAIL_MAILER=postmark.',
        'config_keys' => ['services.postmark.key'],
        'docs_url' => 'https://laravel.com/docs/mail#postmark-driver',
        'wired_in_code' => true,
        'mail_driver' => 'postmark',
    ],
    [
        'key' => 'mail_resend',
        'name' => 'Resend',
        'category' => 'Почта',
        'purpose' => 'Драйвер отправки почты Laravel — используется только если MAIL_MAILER=resend.',
        'config_keys' => ['services.resend.key'],
        'docs_url' => 'https://laravel.com/docs/mail#resend-driver',
        'wired_in_code' => true,
        'mail_driver' => 'resend',
    ],
    [
        'key' => 'mail_ses',
        'name' => 'AWS SES',
        'category' => 'Почта',
        'purpose' => 'Драйвер отправки почты Laravel — используется только если MAIL_MAILER=ses.',
        'config_keys' => ['services.ses.key', 'services.ses.secret'],
        'docs_url' => 'https://laravel.com/docs/mail#ses-driver',
        'wired_in_code' => true,
        'mail_driver' => 'ses',
    ],
    [
        'key' => 'slack',
        'name' => 'Slack-уведомления',
        'category' => 'Уведомления',
        'purpose' => 'Канал Laravel-уведомлений для отправки сообщений в Slack-канал.',
        'config_keys' => ['services.slack.notifications.bot_user_oauth_token'],
        'docs_url' => 'https://laravel.com/docs/notifications#slack-notifications',
        'wired_in_code' => false,
    ],

];
