# Архитектура системы

## Общий обзор

Проект построен на архитектуре **сервис-ориентированная** с использованием асинхронной обработки задач через Redis queue. 

## Основные компоненты

### 1. Controllers (HTTP Layer)
Отвечают за обработку API запросов и валидацию входных данных.
- ProjectController
- KeywordController
- DomainController
- ContentController
- PlanController

### 2. Services (Business Logic)
Это ядро приложения. Каждый сервис отвечает за определенную область:

**WaybackService**
- Парсинг Web Archive API
- Кэширование результатов на 1 день

**DomainAvailabilityService**
- Проверка HTTP статуса домена
- Пакетная обработка (batch processing)

**WhoisService**
- WHOIS запросы для проверки регистрации
- Кэширование на 7 дней
- Fallback для несоответствий

**SeoRankService**
- API запросы к seo-rank
- Rate limiting (1000 запросов за день)
- Retry с exponential backoff

**DomainMetricsService**
- Обновление метрик доменов
- Фильтрация по параметрам

**UniqueCheckerService**
- Проверка уникальности контента
- Интеграция с Google/Yandex

### 3. Jobs (Async Processing)
Долгие операции выполняются асинхронно:

```
ParseWaybackJob (high priority)
  ↓
CheckDomainAvailabilityJob (default priority)
  ↓
CheckDomainMetricsJob (low priority)
  ↓
CheckContentUniquenessJob (default priority)
```

### 4. Models (Data Layer)
Элоквент модели с отношениями:

```
Project
  ├─ User (owner)
  ├─ Keywords
  ├─ Domains
  │  ├─ DomainMetric
  │  └─ Contents
  ├─ ContentPlans
  └─ ProjectAccess
```

### 5. Resources (API Layer)
Трансформация моделей в JSON для API:
- ProjectResource
- DomainResource
- DomainMetricResource
- ContentResource
- ContentPlanResource

## Data Flow

### Жизненный цикл проекта

```
1. CREATE PROJECT
   ↓
2. ADD KEYWORDS
   ↓
3. TRIGGER PARSE
   │
   └─→ ParseWaybackJob (async)
       ├─ Fetch from Web Archive
       ├─ Create domains
       └─ Dispatch CheckDomainAvailabilityJob
         ├─ HTTP check
         ├─ WHOIS check
         └─ Mark as available/occupied/dead
           └─ Dispatch CheckDomainMetricsJob (if available)
             ├─ Query seo-rank API
             └─ Update DA/PA/Alexa
   ↓
4. REVIEW CONTENT
   ├─ Check uniqueness (dispatch CheckContentUniquenessJob)
   └─ Add to ContentPlan
   ↓
5. MARK AS TAKEN
   └─ Track progress by team
```

## Caching Strategy

```
Wayback results: 1 day (Cache)
WHOIS data: 7 days (Cache)
Domain metrics: 30 days (Cache)
Content uniqueness: 1 hour (Cache)
```

## Rate Limiting

**seo-rank API:**
- Лимит: 1000 запросов за день
- Стоимость: 0.04$ за 1000 запросов
- Batch size: 100 доменов за раз
- Retry: Exponential backoff 5, 10, 15 минут

## Authorization Model

```
Project Owner
  ├─ Full access
  ├─ Can edit
  ├─ Can delete
  └─ Can grant access to others

Project Collaborator (can_edit=true)
  ├─ View project
  ├─ Edit domains/content
  └─ Mark content as taken

Project Viewer (can_edit=false)
  └─ View only
```

## Performance Considerations

### Database Indexing
```sql
CREATE INDEX idx_domains_status ON domains(project_id, status);
CREATE INDEX idx_domains_available ON domains(project_id, available);
CREATE INDEX idx_content_status ON contents(project_id, status);
CREATE INDEX idx_plans_status ON content_plans(project_id, status);
```

### Query Optimization
- Использование eager loading (with())
- Pagination для больших результатов
- Кэширование часто-используемых данных
- Async processing для длительных операций

### Memory Management
- Batch processing вместо loop processing
- Очистка старых логов через cronjob
- Настройка Redis maxmemory

## Scalability

### Horizontal Scaling
- Multiple queue workers (supervisor)
- Load balancing через Nginx/Apache
- Redis cluster для caching
- Database replication (read replicas)

### Vertical Scaling
- PHP-FPM tuning
- MySQL configuration optimization
- Redis memory allocation

## Error Handling

### Retry Strategy
```php
// ParseWaybackJob: 3 retries
// CheckDomainMetricsJob: 3 retries with backoff
// Others: 2 retries
```

### Logging
- All errors logged to storage/logs/laravel.log
- Queue failures logged separately
- Critical errors sent to monitoring

## Security

### API Security
- Sanctum token authentication
- Policy-based authorization
- CORS configuration
- Input validation
- Rate limiting

### Data Security
- Database encryption for sensitive data
- HTTPS only in production
- Secure file permissions
- Regular backups
