# Настройка и установка

## Предысловия

- PHP 8.1+
- Laravel 11+
- MySQL 8.0+ или PostgreSQL
- Redis (recommended)
- Composer
- Git

## Шаг 1: Клонирование репозитория

```bash
git clone https://github.com/webjeyros/webjeyros-webarchive-parser.git
cd webjeyros-webarchive-parser
```

## Шаг 2: Установка зависимостей

```bash
composer install
```

## Шаг 3: Конфигурация окружения

```bash
cp .env.example .env
php artisan key:generate
```

### Настройка .env

```env
# Основные параметры
APP_NAME="Web Archive Parser"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# База данных
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webarchive_parser
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# Cache и Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# API Ключи
SEORANK_API_KEY=your_seorank_api_key
YANDEX_API_KEY=your_yandex_api_key

# Wayback Machine
WAYBACK_API_URL=https://archive.org/wayback/available
WAYBACK_TIMEOUT=30
```

## Шаг 4: Поднятие базы данных

```bash
php artisan migrate
```

### Наполнение тестовыми данными (option)

```bash
php artisan db:seed
```

## Шаг 5: Настройка Queue Worker

```bash
# В новом terminal
php artisan queue:work --queue=high,default,low --tries=3
```

Для production:

```bash
# Настройка Supervisor для мониторинга worker'a
sudo apt-get install supervisor
```

Настройка `/etc/supervisor/conf.d/webarchive-queue.conf`:

```ini
[program:webarchive-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=high,default,low --tries=3
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/webarchive-queue.log
```

Активирование:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start webarchive-queue:*
```

## Шаг 6: Настройка Web сервера

### Nginx конфигурация

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/webarchive-parser/public;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Апач конфигурация

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/webarchive-parser/public

    <Directory /path/to/webarchive-parser>
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/var/run/php/php8.1-fpm.sock|fcgi://localhost/"
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/webarchive-error.log
    CustomLog ${APACHE_LOG_DIR}/webarchive-access.log combined
</VirtualHost>
```

## Оптимизация Production

### Optimizing Autoloader

```bash
composer install --optimize-autoloader --no-dev
```

### Кэширование конфигурации

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Очистка кэша

```bash
php artisan cache:clear
php artisan view:clear
```

## Мониторинг

### Logs

```bash
# Наблюдать логи
less +F storage/logs/laravel.log

# Очистить логи
php artisan logs:clear
```

### Queue Status

```bash
# Просмотр задач в очереди
php artisan queue:monitor

# Опыт заново задачи через истечение срока
php artisan queue:retry-batch
```

## Отклонение от темплюта

### MySQL

```sql
-- Сохранение базы
php artisan backup:run --only-db

-- Восстановление из бакапа
mysql -u user -p database < backup.sql
```

## SSL/HTTPS

### Let's Encrypt with Certbot

```bash
sudo apt-get install certbot python3-certbot-nginx
sudo certbot certonly --nginx -d your-domain.com

# Обновление конфигурации nginx
sudo certbot --nginx -d your-domain.com
```

### Автоматическое обновление

```bash
sudo certbot renew --dry-run
```

## Troubleshooting

### "Connection refused" при подключении к Redis

```bash
# Проверка Redis
redis-cli ping

# Перезагрузка Redis
sudo systemctl restart redis-server
```

### "Disk quota exceeded" ошибка при сохранении логов

```bash
# Очистка старых логов
find storage/logs -name "*.log" -mtime +30 -delete
```

### Низкая производительность parsing

1. Увеличьте `WAYBACK_TIMEOUT` в .env
2. Увеличьте количество queue workers
3. Проверьте лимиты в .env файле

## Дополнительные ресурсы

- [Laravel Documentation](https://laravel.com/docs)
- [Supervisor Documentation](http://supervisord.org/)
- [Nginx Documentation](https://nginx.org/)
