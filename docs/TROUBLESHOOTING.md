# Отклонение сбоев

## Обычные проблемы

### 1. Queue задачи не выполняются

**Причины:**
- Queue worker не запустен
- Redis не доступен
- Недостаточно памяти/процесса

**Решение:**

```bash
# Проверите Redis
redis-cli ping
# Увидите "PONG" - всё OK

# Перезапустите queue worker
php artisan queue:work

# Обеспечите Supervisor включен
 sudo supervisorctl status webarchive-queue
```

### 2. "WHOIS service error"

**Причины:**
- whois команда не установлена
- Timeout сервера WHOIS

**Решение:**

```bash
# На Linux
sudo apt-get install whois

# На macOS
brew install whois

# Повысите timeout в .env
WHOIS_TIMEOUT=20
```

### 3. SeoRank API ошибки

**Причины:**
- Неверный API ключ
- Превышен лимит rate limit
- Истекся баланс на аккаунте

**Решение:**

```bash
# Проверьте API ключ
curl -H "Authorization: Bearer YOUR_KEY" \
  https://api.seo-rank.com/domain/example.com

# Проверьте rate limit за час
redis-cli GET seorank_api_calls

# Очистите редис кэш если нужно
redis-cli FLUSHDB
```

### 4. Высокая соматам базы

**Причины:**
- Нет оптимизиющих индексов
- Не делется старые данные
- Одно простые кверы

**Решение:**

```bash
# Оптимизируйте MySQL
mysql> OPTIMIZE TABLE domains;
mysql> ANALYZE TABLE keywords;
mysql> REPAIR TABLE contents;

# Очистите старые данные
mysql> DELETE FROM domains WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

# Проверьте наличие индексов
mysql> SHOW INDEXES FROM domains;
```

### 5. High CPU usage в parsing

**Причины:**
- Слишком много одновременных requests
- Неоптимальные queries
- Недостаточно памяти RAM

**Решение:**

```bash
# Настройка .env
WAYBACK_PARSER_PER_PAGE=50  # Уменьшите со 100
WAYBACK_TIMEOUT=60
WHOIS_BATCH_SIZE=25  # Уменьшите с 50
SEORANK_BATCH_SIZE=50  # Уменьшите с 100

# Увеличьте delays
php artisan queue:work --delay=3

# Ограничьте максимум одновременных tasks
php artisan queue:work --max-jobs=100
```

### 6. "Disk quota exceeded"

**Причины:**
- Переполнены логи
- Очередь переполнена
- Временные файлы не удаляются

**Решение:**

```bash
# Очистите логи старше 30 дней
find storage/logs -name "*.log" -mtime +30 -delete

# Очистите временные файлы
php artisan cache:clear
php artisan view:clear

# Посмотрите на использование disk
df -h /path/to/project
du -sh storage/

# Удалите очередь
php artisan queue:flush
```

## Performance Tuning

### 1. MySQL Optimization

```sql
-- max_connections (в my.cnf)
max_connections=1000

-- query_cache_size
query_cache_size=32M
query_cache_type=1

-- innodb_buffer_pool_size
innodb_buffer_pool_size=2G  # ~50-75% от RAM
```

### 2. Redis Optimization

```bash
# redis.conf
maxmemory 512mb
maxmemory-policy allkeys-lru

# Перезагрузка
sudo systemctl restart redis-server
```

### 3. PHP-FPM Optimization

```ini
# /etc/php/8.1/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 10
pm.max_spare_servers = 20
pm.max_requests = 1000
```

### 4. Nginx Optimization

```nginx
worker_processes auto;
worker_connections 2048;

# Gzip compression
gzip on;
gzip_min_length 1000;
gzip_types text/plain application/json;
```

## Debugging

### 1. Enable Query Logging

```php
// config/logging.php
DB::listen(function ($query) {
    Log::debug($query->sql, $query->bindings);
});
```

### 2. Enable Performance Monitoring

```bash
# Посмотрите slow queries
mysql> SET GLOBAL slow_query_log = 'ON';
mysql> SET GLOBAL long_query_time = 2;
```

### 3. Stack Trace Logging

```php
Log::debug('Debug message', debug_backtrace());
```
