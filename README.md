# Notification Service

Сервис для асинхронной отправки уведомлений через разные каналы (email, telegram) с гарантией доставки.

## Локальный запуск

### Требования
- Docker & Docker Compose

### Установка

```bash
# Клонировать репозиторий
git clone <repository-url>
cd notification-service

# Запустить контейнеры
docker-compose up -d

# Установить зависимости
docker exec -it php composer install

# Создать .env файл
docker exec -it php cp .env.example .env

# Сгенерировать ключ приложения
docker exec -it php php artisan key:generate

# Запустить миграции
docker exec -it php php artisan migrate

# Заполнить таблицу users
docker exec -it php php artisan db:seed

# Запустить воркер очередей (в отдельном терминале)
docker exec -it php php artisan queue:work
```

## Доступные сервисы

- **API**: http://localhost — Laravel приложение
- **PostgreSQL**: localhost:5432 — База данных
- **pgAdmin4**: http://localhost:8080 — Управление БД (`admin@admin.com` / `admin`)

## Запуск тестов и проверок

```bash
# Запустить тесты
docker exec -it php php artisan test

# Проверить стиль кода
docker exec -it php ./vendor/bin/pint --test

# Исправить стиль кода
docker exec -it php ./vendor/bin/pint

# Статический анализ (PHPStan level 5)
docker exec -it php ./vendor/bin/phpstan analyse --memory-limit=2G
```

## API Endpoints
Метод	Endpoint	Описание
- **POST**	/api/notifications	Создать уведомление
- **GET**	/api/notifications/{id}	Получить статус
- **GET**	/api/users/{userId}/notifications	История с фильтрацией

## Архитектурные решения

### 1. Паттерн Стратегия для каналов
- **Почему**: Добавление нового канала (SMS, Push) не требует изменения существующего кода
- **Как**: ChannelInterface → конкретные каналы → ChannelManager

### 2. Очередь + Repeat-механизм
- **Почему**: Гарантия доставки при сбоях (таймауты, ошибки API)

- **Как**: При создании → SendNotificationJob в очередь → 3 попытки с задержкой 10 секунд

### 3. Статус-модель
- pending → sent / failed
- **Почему**: Прозрачность для клиента, возможность ручного повтора failed уведомлений

### 4. Database-first подход
- Сохранение уведомления до отправки в очередь
- **Почему**: При падении воркера данные не теряются

## Что улучшить в продакшене

### Мониторинг и алерты
- Добавить метрики (количество failed уведомлений, время в очереди)
- Настроить алерты в Telegram/Slack при падении воркера

### Retry стратегия
- Экспоненциальная задержка вместо фиксированной (10, 30, 60 сек)
- Dead Letter Queue для failed уведомлений с ручным повтором

### Безопасность
- API ключ или JWT для аутентификации запросов
- Rate limiting (ограничение количества запросов от одного user_id)

### Production ready
- Горизонтальное масштабирование воркеров (несколько подов)
- Redis для очередей вместо database (быстрее)
- Graceful shutdown для воркеров
- Health check endpoints (/health, /ready)

### Дополнительные фичи
- Шаблоны уведомлений (переиспользование)
- Отложенная отправка (schedule_at)
- Batch-отправка для одного получателя
- Webhook после отправки

### Performance
- Индексы в БД: (user_id, status, created_at)
- Кэширование контактов пользователей (email/telegram id)