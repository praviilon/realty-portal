# Технический план: портал недвижимости Smart Realty (Laravel, Вехи 1–3)

Основа: SRS_А-Недвижимость.docx, Бэклог_А-Недвижимость.docx, Валидация_полей_А-Недвижимость.xlsx.
Скоуп: Вехи 1–3 (MVP, коммерческая недвижимость, рабочие пространства). Веха 4 — исключена.
Стек: PHP 8.3 + Laravel 11, MySQL 8, Filament 3, Blade + Livewire 3 + Alpine.js, Tailwind + Flowbite. Хостинг: Timeweb (виртуальный хостинг, SSH).

---

## 1. Пакеты и ключевые решения

| Слой | Решение | Зачем именно так |
|---|---|---|
| Auth | Laravel Breeze (Livewire-стек) | Готовый скелет регистрации/входа — не пишем с нуля, только кастомизируем валидацию под xlsx |
| Админка/модерация | Filament 3 | CRUD-панель за часы, а не недели; закрывает обязательный минимум модерации из исключённой Вехи 4 |
| Уведомления (§3.11) | Встроенные database-notifications Laravel | Таблица `notifications` (type, notifiable, data json, read_at) уже есть в фреймворке — не создаём свою |
| Аватар (§3.9) | Intervention Image (серверный ресайз в квадрат 256×256 WebP) | Быстрее в разработке, чем клиентский Canvas-кроппер из спеки; результат для пользователя эквивалентен |
| Избранное/Сравнение/Фото | Полиморфные Eloquent-связи (`favoritable`, `comparable`, `photoable`) | Заменяют ручное поле `realty_type` из SRS — тот же смысл, штатный механизм ORM Laravel |
| Реалтайм (чат, счётчики) | Livewire `wire:poll` (3–5 сек) | Работает на обычном хостинге без WebSocket-сервера и долгоживущих процессов |
| Очереди | `QUEUE_CONNECTION=sync` | На виртуальном хостинге нельзя держать `queue:work` — синхронный режим единственный рабочий вариант |
| Карта | Yandex Maps JS API 3.0 + HTTP Геокодер (ванильный JS в Alpine-компоненте) | Не завязано на бэкенд-язык |
| Полигон на карте | MySQL 8 `ST_Contains` + `SPATIAL INDEX` | PostGIS не нужен на таком масштабе |
| Капча | Яндекс SmartCaptcha (виджет + серверная проверка токена через `Http::asForm()->post()`) | Нативно под RU-аудиторию |
| CSS-фреймворк | Tailwind + Flowbite | Общий стек с Filament (тоже на Tailwind) — меньше дублирования |

---

## 2. Структура проекта

```
app/
├─ Models/
│  ├─ User.php
│  ├─ ResidentialProperty.php
│  ├─ CommercialProperty.php, CommercialRentDetail.php, CommercialSaleDetail.php
│  ├─ Workspace.php, WorkspacePricing.php
│  ├─ PropertyPhoto.php        (полиморфная: photoable)
│  ├─ Favorite.php             (полиморфная: favoritable)
│  ├─ ComparisonList.php, ComparisonItem.php  (полиморфная: comparable)
│  ├─ Chat.php, Message.php
│  └─ Faq.php
├─ Livewire/
│  ├─ Catalog/Search.php, Catalog/Filters.php, Catalog/MapView.php
│  ├─ Property/CreateWizard.php (шаговая форма, отдельные Step-компоненты на тип объекта)
│  ├─ Favorites/List.php, Comparison/Table.php
│  ├─ Chat/Thread.php, Chat/Inbox.php
│  ├─ Notifications/Bell.php
│  └─ Profile/AvatarUpload.php, Profile/PasswordForm.php, Profile/PhoneForm.php
├─ Filament/Resources/
│  ├─ ResidentialPropertyResource.php  (+ actions Одобрить/Отклонить)
│  ├─ CommercialPropertyResource.php   (+ actions)
│  ├─ WorkspaceResource.php            (+ actions)
│  ├─ UserResource.php                 (+ action «Сбросить пароль»)
│  └─ FaqResource.php
├─ Rules/
│  ├─ RussianName.php        (кириллица/пробел/дефис, 2–40 симв., авто-капитализация)
│  ├─ RussianPhone.php       (+7 (XXX) XXX-XX-XX)
│  └─ PasswordPolicy.php     (6–60, лат., заглавные+строчные+цифры)
└─ Http/Controllers/         (минимум — основная логика в Livewire)
```

Тексты ошибок ERR-001–ERR-032 (без аватарных ERR-033–035, они тоже нужны) выносим в `lang/ru/validation.php` одним блоком — это прямой перенос глоссария из xlsx, без дублирования в коде.

---

## 3. Модели и миграции по вехам

### Веха 1 — базовые сущности

**users**
| Поле | Тип | Комментарий |
|---|---|---|
| id, first_name, last_name (nullable) | | правила — RussianName |
| email, phone | unique | RussianPhone для телефона |
| password | hash | PasswordPolicy |
| avatar_path | nullable string | WebP 256×256, обрабатывается Intervention Image |
| role | enum(user, admin) | admin — доступ в Filament |
| timestamps | | |

**residential_properties**
id, user_id, deal_type(sale/rent), property_type(apartment/house/room/...), address, lat, lng, area, floor, total_floors, price, description, status(moderation/active/rejected/archived), rejection_reason (nullable), views_count, timestamps

**property_photos** (полиморфная, переиспользуется всеми тремя типами объектов)
id, photoable_type, photoable_id, path, is_main, sort_order, timestamps

**chats**
id, buyer_id, seller_id, listable_type, listable_id, timestamps
(unique buyer_id+seller_id+listable_type+listable_id — не плодим дубли тредов)

**messages**
id, chat_id, sender_id, text, is_read, timestamps

**faqs**
id, category, question, answer, sort_order, timestamps (редактируется через Filament)

*Уведомления — используем встроенную `notifications` (артизан-команда `php artisan notifications:table`), без своей миграции.*

### Веха 2 — коммерческая недвижимость + избранное/сравнение

**commercial_properties**
id, user_id, deal_type, purpose_type(office/retail/warehouse/free), building_type(administrative/business_center/residential/shopping_center), entrance_type(nullable: separate/common), floor, floor_features(json), total_floors, area, ceiling_height(nullable), heating_type(nullable), finishing_type(nullable), furniture(nullable), address, lat, lng, description, status, rejection_reason, views_count, timestamps

**commercial_rent_details** (1:1 к commercial_properties)
property_id, price_per_month, deposit(nullable), commission(nullable), utilities_included(bool), rent_type(direct/sublease)

**commercial_sale_details** (1:1)
property_id, price, commission(nullable)

**favorites** (полиморфная)
id, user_id, favoritable_type, favoritable_id, added_at, viewed_at(nullable), timestamps
unique(user_id, favoritable_type, favoritable_id)

**comparison_lists**
id, user_id, list_type(residential_sale/residential_rent/commercial_sale/commercial_rent/workspace), timestamps
unique(user_id, list_type)

**comparison_items** (полиморфная)
id, comparison_list_id, comparable_type, comparable_id, added_at
(лимит 3 шт. на список и уникальность типа — на уровне приложения, в Livewire-компоненте)

### Веха 3 — рабочие пространства

**workspaces**
id, user_id, workspace_type(workspace/office/meeting_room/conference_room), workspace_subtype(nullable: fixed/flexible), building_type, entrance_type, floor, floor_features(json), area, access_time(json: [{type, time_from, time_to}]), amenities(json array), extra_options(json array), address, lat, lng, metro_station(nullable), metro_distance_min(nullable), description, status, rejection_reason, deposit(nullable), utilities_included(bool), owner_type(owner/agent), contact_type(calls_and_messages/messages_only), timestamps

**workspace_pricing**
id, workspace_id, period(hour/day/week/month), price

*favorites/comparison_items автоматически расширяются на Workspace — благодаря полиморфным связям миграции для них не меняются.*

---

## 4. Дорожная карта по эпикам

Оценка трудоёмкости условная (S/M/L), для перевода в недели — умножайте на реальную скорость команды.

### Фаза 0 — Фундамент (перед началом эпиков)
1. Установка Laravel + Breeze (Livewire) + Filament + Tailwind/Flowbite — **S**
2. Базовые миграции (users), сидер admin-пользователя — **S**
3. Деплой каркаса на Timeweb (staging), проверка SSH/Composer/document root — **S**
4. Получение ключей: Yandex Maps API, Яндекс SmartCaptcha — **S**

### Фаза 1 — Веха 1
| # | Эпик | Зависит от | Оценка |
|---|---|---|---|
| 1 | Хэдер/футер (Blade-layout) | Фаза 0 | S |
| 2 | Регистрация/авторизация (кастомизация Breeze под xlsx-валидацию) | 1 | M |
| 3 | Каталог + поиск/фильтры (жилая) | 2 | L |
| 4 | Главная страница (форма поиска + подборки + FAQ-аккордеон) | 3 | M |
| 5 | Карта объектов (пины из результатов поиска) | 3 | M |
| 6 | Детальная карточка объявления (жилая) | 3 | M |
| 7 | Создание/редактирование объявления (жилая, шаговая форма) | 2 | L |
| 8 | **Filament: модерация жилой недвижимости** (approve/reject) | 7 | S |
| 9 | Личный кабинет: структура, профиль, аватар (Intervention Image) | 2 | M |
| 10 | Чаты между пользователями (Livewire + polling) | 6 | M |
| 11 | Уведомления (встроенный notifications) | 8, 10 | S |
| 12 | Правовая информация и помощь (статичные страницы) | 1 | S |

> Пункт 8 намеренно идёт сразу после создания объявлений — без него объявления не смогут стать «Активными» уже на этом этапе, тестировать каталог будет не на чем.

### Фаза 2 — Веха 2
| # | Эпик | Зависит от | Оценка |
|---|---|---|---|
| 13 | Коммерческая недвижимость: модель + создание объявления | Фаза 1 | L |
| 14 | Filament: модерация коммерческой недвижимости | 13 | S |
| 15 | Поиск/фильтры — расширение под коммерческую + кнопка «Сбросить фильтры» | 13 | M |
| 16 | Каталог/детальная карточка коммерческой недвижимости | 13, 15 | M |
| 17 | Избранное (полиморфное, вкладки по типам) | 3, 13 | M |
| 18 | Сравнение объявлений (полиморфное, лимит 3) | 3, 13 | M |
| 19 | Карта — выделение области (MySQL spatial) | 5 | M |
| 20 | Карта — выбор адреса при создании объявления (геокодер) | 7, 13 | M |
| 21 | ЛК — смена телефона | 9 | S |
| 22 | ЛК — смена пароля | 9 | S |

### Фаза 3 — Веха 3
| # | Эпик | Зависит от | Оценка |
|---|---|---|---|
| 23 | Рабочие пространства: модель + создание объявления | Фаза 2 | L |
| 24 | Filament: модерация рабочих пространств | 23 | S |
| 25 | Поиск и «Все фильтры» для рабочих пространств | 23 | M |
| 26 | Каталог/карта (фиксированная колонка) рабочих пространств | 23, 25 | M |
| 27 | Детальная карточка рабочего пространства | 23 | M |
| 28 | Избранное/Сравнение — расширение на рабочие пространства | 17, 18, 23 | S |
| 29 | Доработка главной страницы (баннер с вкладками, поиск по типам) | 4, 13, 23 | M |
| 30 | Страница FAQ (отдельная, с категориями) | 12 | S |
| 31 | Анти-флуд: SmartCaptcha после 3 неверных попыток пароля | 2, 22 | S |

---

## 5. Сознательные отступления от исходного SRS

Все — в пользу простоты деплоя и скорости разработки, функционально эквивалентны:

- **JWT + httponly cookies → обычная сессионная авторизация Laravel.** В SRS это было под React SPA + отдельный API; при монолите на Blade/Livewire сессии проще и безопаснее по умолчанию.
- **Кроп аватара на клиенте (Canvas API) → загрузка + серверный ресайз через Intervention Image.** Пользователь всё равно получает квадратный аватар 256×256, просто обрезка происходит на сервере, а не в браузере.
- **Поле `realty_type` как enum → полиморфные связи Eloquent.** Тот же смысл (что за объект — жильё/коммерция/рабочее пространство), но штатный механизм ORM вместо ручной разметки.
- **Полноценная роль «Модератор» с управлением пользователями (§6.5, Веха 4) → один admin-аккаунт в Filament**, который одобряет/отклоняет все три типа объявлений. Без разделения ролей и без раздела «управление пользователями» — это можно добавить позже, если появится отдельный человек на модерацию.
- **Самостоятельное восстановление пароля (§6.2, Веха 4) исключено** → пароль сбрасывается вручную через Filament по запросу пользователя (email/тикет вне системы).
- **Email/телефон не подтверждаются** (соответствующие эпики Вехи 4 исключены) — поля просто вводятся и уникальны в БД, без верификации.

---

## 6. Решения по открытым вопросам

1. **Модерация — один супер-пользователь (admin) в Filament.** Для MVP с одним человеком на модерации это правильный уровень простоты, не над-инженерия. Технически это не «рут» ОС, а обычная запись в `users` с `role = admin`, у которой есть доступ к панели `/admin`. Масштабируется без боли: если объём объявлений вырастет — второй админ добавляется созданием ещё одной строки, схема с самого начала на это рассчитана (см. `role` в миграции `users`). Единственная практическая рекомендация: сложный уникальный пароль для этого аккаунта — он единственный, кто одобряет объявления и сбрасывает пароли пользователям.
2. Оценка в неделях — пропускаем, оставляем условные S/M/L из раздела 4.
3. Стартуем с Фазы 0 — чеклист и команды ниже.

## 7. Фаза 0 — практический чеклист

Важно сразу оговорить: эта консультация выполняется в изолированной песочнице без доступа в интернет, поэтому `composer`/`npm`/`git` я сам запустить не могу — ниже точные команды для вас и готовые фрагменты кода для вставки в проект.

### 7.1 Создание проекта и стека (локально)

```bash
composer create-project laravel/laravel realty-portal
cd realty-portal

# Auth-скелет на Livewire — не пишем регистрацию/вход с нуля
composer require laravel/breeze --dev
php artisan breeze:install livewire
npm install

# Tailwind уже стоит после Breeze, добавляем Flowbite
npm install flowbite
```

В `tailwind.config.js` в `plugins` добавить `require('flowbite/plugin')`, в `content` — путь до `node_modules/flowbite/**/*.js`. В `resources/css/app.css` — `@import` не нужен, Flowbite JS импортируется в `resources/js/app.js`: `import 'flowbite';`.

```bash
npm run build
php artisan migrate
```

### 7.2 Filament и супер-пользователь

```bash
composer require filament/filament:"^3.2" -W
php artisan filament:install --panels
php artisan make:filament-user
```

Последняя команда интерактивная: спросит имя/email/пароль и создаст админа — это и есть супер-пользователь для модерации из п.1.

В миграцию `users` добавляем поле роли:
```php
$table->enum('role', ['user', 'admin'])->default('user');
```

В `app/Models/User.php` — ограничиваем доступ в панель только админам:
```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    // ...

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin';
    }
}
```

### 7.3 Git и подготовка к деплою

```bash
git init
git add .
git commit -m "Initial Laravel + Breeze + Filament scaffold"
```

Создайте приватный репозиторий на GitHub/GitLab и запушьте туда — он понадобится на сервере для `git clone` по SSH.

### 7.4 Деплой каркаса на Timeweb (staging)

По SSH на сервере:
```bash
git clone git@github.com:your-org/realty-portal.git ~/realty-portal
cd ~/realty-portal
composer install --no-dev --optimize-autoloader
cp .env.example .env
# отредактировать .env: DB_*, APP_URL
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan filament:install --panels --force
```

В панели Timeweb укажите document root на `~/realty-portal/public`. Если панель не даёт сменить корень напрямую — есть обходной вариант с переносом `index.php`, напишите, если столкнётесь, подскажу точные строки.

Frontend-ассеты (`public/build`) собираются локально командой `npm run build` из п. 7.1 и заливаются вместе с остальными файлами — Node.js на самом хостинге не нужен.

### 7.5 Ключи внешних API

| Сервис | Где получить | Куда положить |
|---|---|---|
| Yandex Maps JS API + Геокодер | `developer.tech.yandex.ru` → «Подключить API» → «JavaScript API и HTTP Геокодер» | `.env` → `YANDEX_MAPS_API_KEY` |
| Яндекс SmartCaptcha | `cloud.yandex.ru/services/smartcaptcha` → создать капчу для домена | `.env` → `SMARTCAPTCHA_SITE_KEY`, `SMARTCAPTCHA_SERVER_KEY` |

После этого чек-листа каркас проекта разворачивается на стейджинге, супер-пользователь создан, ключи получены — можно переходить к Фазе 1 (эпики Вехи 1) из раздела 4.
