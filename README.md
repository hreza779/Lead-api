# Lead API

A Laravel-based API for lead management with Docker support.

## Requirements

- Docker Engine 20.10+
- Docker Compose 2.0+

## Quick Start with Docker

> [!IMPORTANT]
> **Using External Nginx**: This project is configured to work with an existing nginx container. See [NGINX_SETUP.md](NGINX_SETUP.md) for detailed instructions on connecting to your existing nginx.

### 1. Clone and Setup

```bash
# Copy environment file
cp .env.docker .env

# Build and start all services
docker-compose up -d

# Install PHP dependencies
docker-compose exec app composer install

# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate

# Generate Swagger documentation
docker-compose exec app php artisan l5-swagger:generate
```

### 2. Connect to Your Nginx Container

Follow the detailed instructions in [NGINX_SETUP.md](NGINX_SETUP.md) to:
1. Create a shared Docker network
2. Connect your nginx container to the network
3. Configure nginx to proxy to the app container

### 3. Access the Application

After nginx configuration:
- **API**: http://your-domain.com (via your nginx)
- **API Documentation**: http://your-domain.com/api/documentation
- **Mailhog (Email Testing)**: http://localhost:8025
- **Direct Health Check**: Connect to lead_app:9000 from nginx

## Docker Services

| Service | Container Name | Ports | Purpose |
|---------|---------------|-------|----------|
| app | lead_app | 9000 | PHP 8.4 FPM |
| mysql | lead_mysql | 3306 | Database |
| redis | lead_redis | 6379 | Cache & Queues |
| mailhog | lead_mailhog | 1025, 8025 | Email Testing |

**Note**: This setup uses your existing nginx container. The app container is exposed on port 9000 for nginx to connect via FastCGI.

## Development Workflow

### Common Commands

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# View logs
docker-compose logs -f
docker-compose logs -f app  # Specific service

# Access app container shell
docker-compose exec app bash

# Run artisan commands
docker-compose exec app php artisan [command]

# Run migrations
docker-compose exec app php artisan migrate

# Create migration
docker-compose exec app php artisan make:migration [name]

# Run seeders
docker-compose exec app php artisan db:seed

# Clear caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear

# Run tests
docker-compose exec app php artisan test
```

### Database Management

```bash
# Access MySQL CLI
docker-compose exec mysql mysql -u lead -psecret lead

# Export database
docker-compose exec mysql mysqldump -u lead -psecret lead > backup.sql

# Import database
docker-compose exec -T mysql mysql -u lead -psecret lead < backup.sql

# Fresh database (drop all tables and re-migrate)
docker-compose exec app php artisan migrate:fresh

# Fresh database with seeders
docker-compose exec app php artisan migrate:fresh --seed
```

### Queue Management

```bash
# Run queue worker
docker-compose exec app php artisan queue:work

# Run queue worker with specific connection
docker-compose exec app php artisan queue:work redis

# List failed jobs
docker-compose exec app php artisan queue:failed

# Retry failed jobs
docker-compose exec app php artisan queue:retry all
```

### Redis Commands

```bash
# Access Redis CLI
docker-compose exec redis redis-cli

# Monitor Redis commands
docker-compose exec redis redis-cli MONITOR

# Flush all Redis data
docker-compose exec redis redis-cli FLUSHALL
```

## Troubleshooting

### Permission Issues

```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chmod -R 775 /var/www/html/storage
```

### Rebuild Containers

```bash
# Rebuild without cache
docker-compose build --no-cache

# Rebuild specific service
docker-compose build --no-cache app

# Rebuild and restart
docker-compose down && docker-compose up -d --build
```

### Reset Everything

```bash
# Stop and remove all containers, networks, and volumes
docker-compose down -v

# Rebuild and start fresh
docker-compose up -d --build
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

### Check Service Health

```bash
# Check running containers
docker-compose ps

# Check logs for errors
docker-compose logs app

# Test database connection
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

## Environment Variables

Key environment variables in `.env`:

```env
DB_HOST=mysql          # Docker service name
DB_PORT=3306
DB_DATABASE=lead
DB_USERNAME=lead
DB_PASSWORD=secret

REDIS_HOST=redis       # Docker service name
REDIS_PORT=6379

MAIL_HOST=mailhog      # Docker service name
MAIL_PORT=1025
```

## Production Deployment

For production, modify `docker-compose.yml`:

1. Change build target to `production`:
```yaml
app:
  build:
    target: production
```

2. Remove volume mounts (use built-in code)
3. Set `APP_ENV=production` and `APP_DEBUG=false`
4. Use secure passwords and Redis password
5. Configure proper domain and SSL certificates

## API Documentation

Swagger documentation is available at `/api/documentation` after running:

```bash
docker-compose exec app php artisan l5-swagger:generate
```

## Tech Stack

- **Framework**: Laravel 12
- **PHP**: 8.4
- **Database**: MySQL 8.0
- **Cache/Queue**: Redis
- **Web Server**: Nginx
- **API Documentation**: L5-Swagger

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
