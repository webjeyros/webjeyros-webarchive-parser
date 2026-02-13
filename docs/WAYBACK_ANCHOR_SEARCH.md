# Wayback Anchor Search Feature

## Описание

Это обновление модифицирует поиск по ключевым словам в Wayback Machine. Вместо поиска вхождения ключевого слова в домене, система теперь ищет вхождение ключевого слова в заголовках (`title`/`text`) и сниппетах (`snippet`) сохраненных страниц.

## Использованный Endpoint

```
https://web.archive.org/__wb/search/anchor?q={keyword}
```

### Пример запроса

```bash
curl "https://web.archive.org/__wb/search/anchor?q=проститутки"
```

### Структура ответа

Endpoint возвращает JSON массив объектов со следующей структурой:

```json
{
  "name": "domainname.com",
  "display_name": "www.domainname.com",
  "text": "Текст из заголовка или метаданных",
  "snippet": "Сниппет из поискового результата",
  "link": "http://domainname.com/",
  "first_captured": 2010,
  "last_captured": 2016,
  "capture": 65181,
  "webpage": 8358,
  "image": 56,
  "video": 0,
  "audio": 0
}
```

## Новые компоненты

### 1. WaybackAnchorService (`app/Services/WaybackAnchorService.php`)

Новый сервис для работы с Wayback anchor search API:

- **searchByKeyword(string $keyword): Collection** - Основной метод для поиска по ключевому слову
- **clearCache(string $keyword): void** - Очистка кэша поиска

**Кэширование**: Результаты кэшируются на 24 часа

```php
$service = app(WaybackAnchorService::class);
$results = $service->searchByKeyword('проститутки');
```

### 2. Обновленный ParseWaybackJob

- Заменен импорт сервиса с `WaybackService` на `WaybackAnchorService`
- Добавлена обработка новых полей из API ответа
- Все данные из архива теперь сохраняются в модель `Domain`

### 3. Расширенная Domain модель

Добавлены новые поля для хранения информации из архива:

- `first_captured` - Год первого сохранения
- `last_captured` - Год последнего сохранения
- `capture_count` - Количество сохраненных снимков
- `webpage_count` - Количество сохраненных веб-страниц
- `image_count` - Количество сохраненных изображений
- `video_count` - Количество сохраненных видео
- `audio_count` - Количество сохраненного аудио

### 4. Миграция БД

**Файл**: `database/migrations/2026_02_13_000007_add_archive_fields_to_domains_table.php`

Добавляет вышеупомянутые поля в таблицу `domains`.

**Запуск**:
```bash
php artisan migrate
```

## Изменения в логике парсинга

### До обновления
```php
// Поиск только по домену
$results = $waybackService->searchByKeyword('ключевое слово');
// Результаты: домены, содержащие ключевое слово в названии
```

### После обновления
```php
// Поиск по заголовкам и текстовому содержимому
$results = $waybackAnchorService->searchByKeyword('ключевое слово');
// Результаты: домены с сохраненными страницами, содержащими ключевое слово
// ПЛЮС полная информация о сохранениях в архиве
```

## Преимущества

1. **Точность** - Поиск по фактическому контенту, а не только по доменам
2. **Полнота данных** - Получение информации о количестве и типах сохранений
3. **Временная метаинформация** - Знание периода активности сайта
4. **Статистика** - Данные о содержимом страниц в архиве

## Использование

### Создание ключевых слов для поиска

```bash
POST /api/projects/{projectId}/keywords
Content-Type: application/json

{
  "keywords": ["проститутки", "доступные услуги", "интим"]
}
```

### Запуск парсинга

```bash
POST /api/projects/{projectId}/keywords/parse
```

Система автоматически:
1. Запросит Wayback anchor API для каждого ключевого слова
2. Фильтрует результаты по наличию текста в заголовках
3. Сохраняет все найденные домены с полной информацией
4. Создает очередь для проверки доступности домена

## Обработка ошибок

Если Wayback API недоступен или возвращает ошибку:

1. Задание повторяется до 3 раз (значение `$tries` в Job)
2. Логируется ошибка в файл логов
3. Ключевое слово остается в статусе 'pending'

## Примеры ответа API

### Успешный поиск

```json
[
  {
    "name": "example.ru",
    "display_name": "www.example.ru",
    "text": "Пример текста",
    "snippet": "Сниппет поиска",
    "link": "http://example.ru/",
    "first_captured": 2015,
    "last_captured": 2020,
    "capture": 120,
    "webpage": 80,
    "image": 40,
    "video": 0,
    "audio": 0
  }
]
```

### Пустой результат

```json
[]
```

## Миграция существующих данных

Если у вас уже есть накопленные данные, выполните:

```bash
# Создание новых полей
php artisan migrate

# Опционально: очистка кэша
php artisan cache:clear
```

## Тестирование

```php
// Тест сервиса
$service = app(WaybackAnchorService::class);
$results = $service->searchByKeyword('test');

dd($results->toArray());
```

## Замечания

1. Wayback API может быть медленным - используется timeout в 30 секунд
2. Результаты кэшируются на 24 часа для снижения нагрузки
3. Некоторые домены могут иметь пустой текст - они все равно обрабатываются
4. Следите за rate limiting Wayback API

## Хардкод конфигурации

Для изменения параметров отредактируйте `WaybackAnchorService.php`:

```php
private const ANCHOR_API_URL = 'https://web.archive.org/__wb/search/anchor';
private const CACHE_TTL = 86400; // 24 часа
private const TIMEOUT = 30; // секунды
```

## Поддержка

Для сообщения об ошибках или предложений создайте issue в репозитории.
