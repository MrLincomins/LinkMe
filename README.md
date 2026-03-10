# LinkMe

Self-hosted сервис сокращения ссылок с функциями редиректа, защитой паролем, поддержкой нескольких доменов и аналитикой посещений. Построен на Laravel.

## Технологический стек

- **PHP 8.2+** / **Laravel 12** — бэкенд, Eloquent ORM, события, кэширование, мягкое удаление
- **Laravel Sanctum** — токен-аутентификация API
- **MySQL 8** — база данных
- **Redis 7** — кэширование и очереди
- **Nginx** — веб-сервер
- **Docker** — контейнеризация
- **PHPUnit** — 40+ функциональных тестов
- **Tailwind CSS** — фронтенд

## Возможности

- Короткие ссылки с пользовательскими кодами и типами редиректов (301 / 302 / 307 / 308)
- Поддержка нескольких доменов — коды уникальны в рамках домена
- Автоматическая верификация доменов при первом запросе
- Редирект корня домена — настраиваемое перенаправление
- Защита паролем — до 5 паролей на ссылку с опциональным целевым URL, доп. параметрами и лимитом использования
- Проброс query-параметров на целевой URL
- Инъекция UTM-параметров и сегментов пути
- Атомарные счётчики переходов
- Аналитика посещений (IP, user agent, referer) через очередь событий
- Мягкое удаление и восстановление ссылок
- Админ-панель с ограничением по домену
- Кэширование доменов с автоинвалидацией

## Архитектура

```
app/
├── Enums/
│   └── RedirectType.php              # Enum типов редиректа
├── Events/
│   └── LinkVisited.php               # Событие посещения ссылки
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── AuthController.php    # Регистрация, вход, выход
│   │   │   ├── ShortDomainController.php
│   │   │   └── ShortLinkController.php
│   │   └── RedirectController.php    # Обработка редиректов
│   ├── Middleware/
│   │   └── AdminDomainMiddleware.php # Ограничение /admin по домену
│   ├── Requests/                     # Валидация
│   └── Resources/                    # Форматирование ответов
├── Listeners/
│   ├── IncrementHitCount.php         # Обновление счётчиков
│   └── LogVisit.php                  # Логирование посещений
├── Models/
│   ├── LinkVisit.php
│   ├── ShortDomain.php
│   ├── ShortLink.php                 # SoftDeletes
│   └── ShortLinkPassword.php
├── Observers/
│   └── ShortDomainObserver.php       # Инвалидация кэша
└── Services/
    ├── DomainService.php             # Резолвинг доменов + кэш
    └── RedirectService.php           # Построение URL + редирект
```

## Docker-окружение

Проект полностью контейнеризирован. Состав сервисов:

| Сервис | Образ | Назначение                                 |
|--------|-------|--------------------------------------------|
| linkme-php | PHP 8.2-FPM (кастомный) | Приложение Laravel + Node.js 20 для фронта |
| linkme-nginx | nginx | Веб-сервер                                 |
| linkme-mysql | mysql:8 | База данных                                |
| linkme-redis | redis:7 | Кэш и очереди                              |
| linkme-queue | PHP 8.2-FPM (кастомный) | Воркер очередей (`queue:work`)             |

При старте контейнера `linkme-php` автоматически выполняются: `composer update`, `php artisan migrate --force`, `npm install` и `npm run dev`. Контейнер `linkme-queue` ожидает готовности `vendor/autoload.php` и запускает воркер.

## Установка

```bash
# Клонирование
git clone https://github.com/yourusername/linkme.git
cd linkme

# Окружение
cp www/.env.example www/.env

# Добавьте в .env:
# ADMIN_DOMAIN=localhost
# DEFAULT_SHORT_DOMAIN=localhost

# Запуск
docker compose up -d
```

Все зависимости (Composer, npm), миграции и сборка фронтенда выполняются автоматически при первом запуске.

## Тестирование

```bash
docker exec -it linkme-php php artisan test
```

Покрытие: все типы редиректов, проброс query, инъекция query/path, счётчики, защита паролем, лимиты паролей, мягкое удаление, кэширование доменов, события, аутентификация.

## API

Все маршруты с префиксом `/api`. Защищённые требуют заголовок `Authorization: Bearer {token}`.

### Аутентификация

| Метод | Эндпоинт | Описание | Авт. |
|-------|----------|----------|------|
| POST | /api/register | Регистрация | Нет |
| POST | /api/login | Получение токена | Нет |
| POST | /api/logout | Отзыв токена | Да |
| GET | /api/user | Текущий пользователь | Да |

### Ссылки

| Метод | Эндпоинт | Описание | Авт. |
|-------|----------|----------|------|
| GET | /api/links | Список (пагинация) | Да |
| POST | /api/links | Создать | Да |
| GET | /api/links/{id} | Получить с паролями | Да |
| PUT | /api/links/{id} | Обновить | Да |
| DELETE | /api/links/{id} | Мягкое удаление | Да |
| GET | /api/links/trashed | Удалённые | Да |
| POST | /api/links/{id}/restore | Восстановить | Да |
| DELETE | /api/links/{id}/force | Удалить навсегда | Да |
| GET | /api/links/{id}/stats | Аналитика | Да |

Фильтры: `search`, `domain_id`, `per_page`, `page`

### Домены

| Метод | Эндпоинт | Описание | Авт. |
|-------|----------|----------|------|
| GET | /api/domains | Список с кол-вом ссылок | Да |
| POST | /api/domains | Создать | Да |
| GET | /api/domains/{id} | Детали | Да |
| PUT | /api/domains/{id} | Обновить | Да |
| DELETE | /api/domains/{id} | Удалить (ошибка если есть ссылки) | Да |

## Примеры

```bash
# Регистрация
curl -X POST http://localhost/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Admin","email":"admin@test.com","password":"password123","password_confirmation":"password123"}'

# Вход
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"password123"}'
# → {"data":{"user":{...},"token":"1|abc..."}}

# Создание домена
curl -X POST http://localhost/api/domains \
  -H "Authorization: Bearer 1|abc..." \
  -H "Content-Type: application/json" \
  -d '{"name":"localhost","target_url":"https://github.com"}'

# Создание короткой ссылки
curl -X POST http://localhost/api/links \
  -H "Authorization: Bearer 1|abc..." \
  -H "Content-Type: application/json" \
  -d '{"code":"gh","domain_id":1,"target_url":"https://github.com","redirect_type":"301"}'

# Переход: http://localhost/gh → https://github.com

# Статистика
curl http://localhost/api/links/1/stats \
  -H "Authorization: Bearer 1|abc..."
```

## Дополнительно

**Админ-панель** — доступна по `/admin` только с домена из `ADMIN_DOMAIN`. С других доменов вернёт 404.

**Проброс query** — создайте ссылку с `"forward_query": true`, параметры из URL будут переданы на целевой адрес.

**Инъекция query/path** — используйте поля `extra_query` и `extra_path` при создании ссылки для автоматического добавления UTM-параметров или сегментов пути.
