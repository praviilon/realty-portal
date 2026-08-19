# Первичный деплой Smart Realty на хостинг — пошаговая инструкция

---

## Часть 1. Код → GitHub

### Шаг 1. Создать репозиторий на GitHub

1. Зайдите на github.com под своим аккаунтом.
2. Нажмите **New repository** (зелёная кнопка справа вверху или на github.com/new).
3. Имя репозитория, например `realty-portal`.
4. Обязательно выберите **Private** (это ваш рабочий код, не для публики).
5. Ничего не отмечайте (не добавляйте README, .gitignore, license) — репозиторий должен быть пустым.
6. Нажмите **Create repository**.

Откроется страница с инструкциями и адресом репозитория — он понадобится дальше, что-то вроде:
`https://github.com/ваш-логин/realty-portal.git`

### Шаг 2. Установить Git на компьютер (если ещё не установлен)

1. Откройте PowerShell (Win + R → `powershell` → Enter) и введите:
   ```
   git --version
   ```
2. Если версия показалась — Git уже есть, переходите к шагу 3.
3. Если ошибка «не является командой» — скачайте Git с git-scm.com/download/win, установите с настройками по умолчанию (просто нажимать «Next»), затем перезапустите PowerShell.

### Шаг 3. Распаковать архив

1. В папке `Realty_portal` нажмите правой кнопкой на `realty-portal-source.zip` → «Извлечь всё».
2. Получится папка, например `realty-portal-source`. Переименуйте её в `realty-portal` для порядка.

### Шаг 4. Отправить код на GitHub

В PowerShell:

```powershell
cd C:\Users\richa\Realty_portal\realty-portal
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/ВАШ-ЛОГИН/realty-portal.git
git push -u origin main
```

На команде `git push` Git спросит логин и пароль. Пароль от аккаунта GitHub уже не подходит — нужен **Personal Access Token**:

1. На GitHub: аватар справа вверху → **Settings** → внизу слева **Developer settings** → **Personal access tokens** → **Tokens (classic)** → **Generate new token (classic)**.
2. Дайте название (например `realty-deploy`), поставьте галочку **repo** (полный доступ к репозиториям), нажмите **Generate token**.
3. Скопируйте токен (он покажется один раз) и вставьте его вместо пароля, когда Git его спросит. Логин — ваш обычный логин GitHub.

После этого код появится в репозитории — можно проверить, обновив страницу репозитория на GitHub.

### Шаг 5. Собрать фронтенд-файлы (JS/CSS)

Node.js нужен только у вас на компьютере — на хостинге его не будет, поэтому собранные файлы заливаются как обычные статичные файлы.

1. Проверьте, установлен ли Node.js: `node -v` в PowerShell. Если нет — скачайте с nodejs.org (кнопка LTS), установите по умолчанию.
2. В той же папке проекта:
   ```powershell
   npm install
   npm run build
   ```
3. Эта команда создаёт папку `public/build`. Обычно она в `.gitignore` и не попадает в репозиторий — надо добавить её принудительно:
   ```powershell
   git add public/build -f
   git commit -m "Add built frontend assets"
   git push
   ```

Повторяйте шаг 5 каждый раз, когда меняете что-то во фронтенде (CSS/JS), перед тем как обновлять сервер.

---

## Часть 2. Хостинг Timeweb

### Шаг 6. Завести хостинг (если ещё не куплен)

На timeweb.com нужен тариф, который поддерживает PHP 8.2+ и MySQL, с доступом по SSH (в описании тарифа должно быть указано «SSH-доступ»). После оформления в панели управления Timeweb будут:
- адрес сервера для SSH,
- логин/пароль (или ключ) для SSH,
- раздел «Базы данных» для создания MySQL-базы,
- раздел с доменом/document root сайта.

Если хостинг уже куплен — переходите сразу к шагу 7, взяв данные для подключения из панели Timeweb.

### Шаг 7. Создать базу данных

В панели Timeweb, в разделе «Базы данных»: создайте новую MySQL-базу, задайте имя базы, пользователя и пароль — их запишите, они понадобятся в `.env`.

### Шаг 8. Подключиться к серверу по SSH

В PowerShell:
```powershell
ssh ваш_логин@адрес_сервера
```
Введёте пароль (или он подключится по ключу, если вы его настраивали в панели Timeweb). Дальше все команды — уже на сервере.

### Шаг 9. Скачать код на сервер

Так как репозиторий приватный, для `git clone` по HTTPS сервер тоже спросит логин/токен (тот же Personal Access Token с шага 4):

```bash
git clone https://github.com/ВАШ-ЛОГИН/realty-portal.git ~/realty-portal
cd ~/realty-portal
```

### Шаг 10. Установить зависимости PHP

```bash
composer install --no-dev --optimize-autoloader
```

Если команда `composer` не найдена — в поддержке Timeweb уточните точный путь (обычно `/usr/local/bin/composer` или похожий, у них это описано в базе знаний хостинга).

### Шаг 11. Настроить .env

```bash
cp .env.example .env
php artisan key:generate
nano .env
```

В файле поправьте (стрелками перемещаетесь, сохраняете — Ctrl+O, Enter, выходите — Ctrl+X):

- `APP_URL=https://ваш-домен.ru`
- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_HOST=` — обычно `localhost` или адрес из панели Timeweb
- `DB_DATABASE=` — имя базы из шага 7
- `DB_USERNAME=` — пользователь базы из шага 7
- `DB_PASSWORD=` — пароль базы из шага 7
- `YANDEX_MAPS_API_KEY=` — получить на developer.tech.yandex.ru (см. шаг 14)
- `SMARTCAPTCHA_SITE_KEY=` и `SMARTCAPTCHA_SERVER_KEY=` — получить на cloud.yandex.ru (см. шаг 14)

### Шаг 12. Накатить базу и подготовить проект

```bash
php artisan migrate --force
php artisan storage:link
php artisan filament:install --panels --force
```

### Шаг 13. Настроить корень сайта (document root)

В панели хостинга, в настройках домена/сайта, найдите поле «Директория сайта» или «Document root» и укажите:
```
/realty-portal/public
```
(точный вид пути зависит от того, как Timeweb монтирует вашу домашнюю директорию — если в интерфейсе не получается указать вложенный путь до `public`, напишите мне, подскажу обходной способ через перенос `index.php`).

**Важное обновление!!** Хостинг Timeweb жестко привязывает директорию к корневой папке public_html, и изменить ее название в настройках панели нельзя. Проблема решается созданием символической ссылки (symlink).
Сначала надо удалить дефолтную папку realty-portal/public_html (если она есть). Затем через SSH консоль ввести команды:

```bash
cd ~/realty-portal
ln -s public public_html
```


### Шаг 14. Получить ключи API (карта и капча)

1. **Яндекс.Карты**: зайдите на developer.tech.yandex.ru, создайте приложение, включите «JavaScript API и HTTP Геокодер», скопируйте ключ в `YANDEX_MAPS_API_KEY`.
2. **SmartCaptcha**: зайдите на cloud.yandex.ru, в разделе SmartCaptcha создайте капчу для вашего домена, скопируйте site key и server key в `SMARTCAPTCHA_SITE_KEY` / `SMARTCAPTCHA_SERVER_KEY`.

После изменения `.env` перезапустите кэш конфигурации:
```bash
php artisan config:cache
```

### Шаг 15. Создать администратора

```bash
php artisan make:filament-user
```
Введите имя, email и пароль администратора.

Важно: эта команда **не делает пользователя администратором по факту** — есть отдельное поле `role` в базе, которое она не трогает. Без следующего шага вход в `/admin` не сработает:

```bash
php artisan tinker
```
внутри tinker:
```php
\App\Models\User::where('email', 'ваш@email')->update(['role' => 'admin']);
exit
```

### Шаг 16. Проверка

Откройте в браузере:
- `https://ваш-домен.ru/catalog` — должен открыться каталог объявлений;
- `https://ваш-домен.ru/admin` — должна открыться форма входа в админку, а после входа под созданным пользователем — панель Filament.

Если объявление с картой открывается и карта не рисуется (только заглушка) — проверьте, что `YANDEX_MAPS_API_KEY` действительно указан в `.env` и вы сделали `config:cache` после этого.

---

## Что делать при обновлении кода в будущем

После любых изменений в коде (у себя на компьютере или здесь, в чате со мной):

```powershell
# на компьютере: закоммитить и запушить
git add .
git commit -m "описание изменений"
npm run build  # если менялся фронтенд
git add public/build -f
git commit -m "rebuild assets"
git push
```

```bash
# на сервере: подтянуть изменения
cd ~/realty-portal
git pull
composer install --no-dev --optimize-autoloader  # если менялся composer.json
php artisan migrate --force  # если были новые миграции
php artisan optimize:clear
php artisan config:cache
```

---

Если на каком-то шаге появится ошибка — просто пришлите мне точный текст ошибки, разберём на месте.