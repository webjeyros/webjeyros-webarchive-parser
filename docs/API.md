# API Документация

## Аутентификация

Все API endpoints требуют токен Sanctum. Получите токен для авторизации:

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "password"}'
```

Включайте токен в заголовок `Authorization`:

```bash
Authorization: Bearer YOUR_TOKEN_HERE
```

## Версионирование

Все endpoints используют префикс `/api/v1`.

## Базовые операции

### Создание проекта

```bash
POST /api/v1/projects
Content-Type: application/json

{
  "name": "SEO Mining Project",
  "description": "Поиск авторитетных доменов"
}
```

Ответ (201 Created):

```json
{
  "id": 1,
  "name": "SEO Mining Project",
  "description": "Поиск авторитетных доменов",
  "status": "idle",
  "user_id": 1,
  "keywords_count": 0,
  "domains_count": 0,
  "contents_count": 0,
  "created_at": "2026-02-13T14:34:00Z",
  "updated_at": "2026-02-13T14:34:00Z"
}
```

### Получение списка проектов

```bash
GET /api/v1/projects?page=1
```

Ответ:

```json
{
  "data": [
    {
      "id": 1,
      "name": "SEO Mining Project",
      "status": "idle",
      "keywords_count": 5,
      "domains_count": 150,
      "contents_count": 45
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/v1/projects?page=1",
    "last": "http://localhost:8000/api/v1/projects?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

## Ключевые слова и парсинг

### Добавление ключевых слов

```bash
POST /api/v1/projects/1/keywords
Content-Type: application/json

{
  "keywords": [
    "machine learning",
    "artificial intelligence",
    "deep learning",
    "neural networks"
  ]
}
```

Ответ (201 Created):

```json
{
  "message": "Created 4 keywords",
  "count": 4
}
```

### Запуск парсинга Web Archive

```bash
POST /api/v1/projects/1/parse
```

Ответ:

```json
{
  "message": "Parsing started",
  "keywords_count": 4
}
```

Процесс парсинга выполняется асинхронно через очереди. Статус проекта изменится на `parsing`.

## Управление доменами

### Получение доменов проекта

```bash
GET /api/v1/projects/1/domains?status=available&page=1
```

Параметры фильтрации:
- `status` - фильтр по статусу (new, checking, available, occupied, dead, in_work)
- `available` - фильтр по доступности (true/false)

Ответ:

```json
{
  "data": [
    {
      "id": 1,
      "domain": "example.com",
      "status": "available",
      "status_label": "Свободен",
      "available": true,
      "http_status": 404,
      "title": "Old Website",
      "snippet": "Welcome to our old website...",
      "archived_url": "http://web.archive.org/web/20230101000000/example.com",
      "metric": {
        "id": 1,
        "da": 45,
        "pa": 38,
        "alexa_rank": 1500000,
        "backlinks_count": 234,
        "checked_at": "2026-02-13T14:34:00Z"
      },
      "checked_at": "2026-02-13T14:34:00Z",
      "created_at": "2026-02-13T14:33:00Z",
      "updated_at": "2026-02-13T14:34:00Z"
    }
  ]
}
```

### Получение деталей домена

```bash
GET /api/v1/projects/1/domains/1
```

### Проверка метрик домена

```bash
POST /api/v1/projects/1/domains/1/check-metrics
```

Ответ:

```json
{
  "message": "Metrics check queued"
}
```

### Экспорт доменов

```bash
GET /api/v1/projects/1/domains/export
```

Ответ (CSV-like JSON):

```json
[
  {
    "domain": "example.com",
    "da": 45,
    "pa": 38,
    "alexa_rank": 1500000,
    "backlinks": 234
  }
]
```

## Управление контентом

### Получение контента

```bash
GET /api/v1/projects/1/content?status=unique&page=1
```

Параметры:
- `status` - pending, unique, duplicate
- `is_unique` - true/false

Ответ:

```json
{
  "data": [
    {
      "id": 1,
      "title": "Article Title",
      "url": "http://example.com/article",
      "snippet": "First paragraph of content...",
      "status": "unique",
      "is_unique": true,
      "unique_checked_at": "2026-02-13T14:34:00Z",
      "domain": {
        "id": 1,
        "domain": "example.com"
      },
      "created_at": "2026-02-13T14:33:00Z"
    }
  ]
}
```

### Проверка уникальности контента

```bash
POST /api/v1/projects/1/content/1/check-uniqueness
```

Ответ:

```json
{
  "message": "Uniqueness check queued"
}
```

## Планы работы

### Получение плана проекта

```bash
GET /api/v1/projects/1/plan
```

Ответ:

```json
{
  "data": [
    {
      "id": 1,
      "content": {
        "id": 1,
        "title": "Article Title",
        "url": "http://example.com/article",
        "is_unique": true
      },
      "user_id": 1,
      "user_name": "John Doe",
      "status": "pending",
      "taken_at": null,
      "created_at": "2026-02-13T14:33:00Z"
    }
  ]
}
```

### Добавление контента в план

```bash
POST /api/v1/projects/1/plan
Content-Type: application/json

{
  "content_id": 1
}
```

Ответ (201 Created):

```json
{
  "id": 1,
  "content": {...},
  "user_id": 1,
  "user_name": "John Doe",
  "status": "pending",
  "taken_at": null
}
```

### Отметить контент как взятый

```bash
PATCH /api/v1/projects/1/plan/1/taken
```

Ответ:

```json
{
  "id": 1,
  "status": "taken",
  "taken_at": "2026-02-13T14:35:00Z"
}
```

## Управление доступом

### Предоставление доступа к проекту

```bash
POST /api/v1/projects/1/access
Content-Type: application/json

{
  "user_id": 2,
  "can_edit": true
}
```

### Получение списка доступов

```bash
GET /api/v1/projects/1/access
```

### Отзыв доступа

```bash
DELETE /api/v1/projects/1/access/2
```

## Коды ошибок

- `200 OK` - Успешно
- `201 Created` - Ресурс создан
- `204 No Content` - Успешно, без содержимого
- `400 Bad Request` - Ошибка валидации
- `401 Unauthorized` - Требуется аутентификация
- `403 Forbidden` - Доступ запрещен
- `404 Not Found` - Ресурс не найден
- `422 Unprocessable Entity` - Ошибка обработки
- `500 Internal Server Error` - Ошибка сервера

## Пример полного цикла

```bash
# 1. Создать проект
curl -X POST http://localhost:8000/api/v1/projects \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "My Project"}'

# 2. Добавить ключевые слова
curl -X POST http://localhost:8000/api/v1/projects/1/keywords \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"keywords": ["keyword1", "keyword2"]}'

# 3. Запустить парсинг
curl -X POST http://localhost:8000/api/v1/projects/1/parse \
  -H "Authorization: Bearer TOKEN"

# 4. Проверить статус доменов
curl -X GET "http://localhost:8000/api/v1/projects/1/domains?status=available" \
  -H "Authorization: Bearer TOKEN"

# 5. Экспортировать результаты
curl -X GET http://localhost:8000/api/v1/projects/1/domains/export \
  -H "Authorization: Bearer TOKEN"
```
