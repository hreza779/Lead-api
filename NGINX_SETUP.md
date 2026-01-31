# راهنمای اتصال به Nginx موجود سرور

## مرحله 1: ایجاد شبکه مشترک

ابتدا یک شبکه Docker به نام `nginx_network` ایجاد کنید که هم nginx موجود و هم app container به آن متصل شوند:

```bash
docker network create nginx_network
```

## مرحله 2: اتصال Nginx موجود به شبکه

nginx container موجود خود را به شبکه جدید متصل کنید:

```bash
docker network connect nginx_network <نام_nginx_container_شما>
```

برای پیدا کردن نام container nginx خود:

```bash
docker ps | grep nginx
```

## مرحله 3: راه‌اندازی پروژه Lead

```bash
# به دایرکتوری پروژه بروید
cd /path/to/Lead

# فایل محیطی را کپی کنید
cp .env.docker .env

# Container‌ها را بسازید و اجرا کنید
docker-compose up -d

# وابستگی‌ها را نصب کنید
docker-compose exec app composer install

# کلید برنامه را تولید کنید
docker-compose exec app php artisan key:generate

# Migration‌ها را اجرا کنید
docker-compose exec app php artisan migrate

# مستندات API را بسازید
docker-compose exec app php artisan l5-swagger:generate
```

## مرحله 4: پیکربندی Nginx

### روش 1: استفاده از Volume Mount

اگر nginx شما از volume mount استفاده می‌کند:

```bash
# فایل lead-site.conf را کپی کنید
cp docker/nginx/lead-site.conf /path/to/nginx/conf.d/lead.conf

# یا اگر volume دارید:
docker cp docker/nginx/lead-site.conf <nginx_container>:/etc/nginx/conf.d/lead.conf
```

### روش 2: استفاده از docker exec

```bash
# محتوای فایل را به nginx کپی کنید
docker cp docker/nginx/lead-site.conf <nginx_container_name>:/etc/nginx/conf.d/lead.conf

# nginx را reload کنید
docker exec <nginx_container_name> nginx -s reload
```

## مرحله 5: اشتراک‌گذاری فایل‌ها با Nginx

برای اینکه nginx بتواند فایل‌های استاتیک را سرو کند، باید مسیر پروژه را با nginx به اشتراک بگذارید:

### روش الف: Volume مشترک

اگر nginx شما از volume استفاده می‌کند:

```bash
# nginx container را متوقف کنید
docker stop <nginx_container_name>

# nginx را با volume مشترک دوباره راه‌اندازی کنید
docker run -d \
  --name <nginx_container_name> \
  --network nginx_network \
  -v /path/to/Lead:/var/www/html/lead:ro \
  -v /path/to/nginx/conf:/etc/nginx/conf.d \
  -p 80:80 \
  nginx:latest
```

### روش ب: استفاده از bind mount موجود

اگر nginx شما قبلاً دارای bind mount است:

```bash
# پروژه را در مسیر bind mount کپی کنید
cp -r /path/to/Lead /path/to/nginx/webroot/lead
```

## مرحله 6: تنظیم فایل lead-site.conf

فایل `docker/nginx/lead-site.conf` را ویرایش کنید:

```nginx
server_name your-domain.com;  # دامنه خود را وارد کنید

# مسیر را بر اساس volume mount خود تنظیم کنید
root /var/www/html/lead/public;
```

## بررسی اتصال

بررسی کنید که nginx به app container دسترسی دارد:

```bash
# از داخل nginx container
docker exec <nginx_container_name> ping lead_app

# یا بررسی اتصال به port 9000
docker exec <nginx_container_name> nc -zv lead_app 9000
```

## دستورات مفید

```bash
# مشاهده شبکه‌های متصل به container
docker inspect <container_name> | grep Networks -A 20

# مشاهده لیست شبکه‌ها
docker network ls

# مشاهده جزئیات شبکه
docker network inspect nginx_network

# بررسی log‌های nginx
docker logs <nginx_container_name>

# بررسی log‌های app
docker-compose logs -f app
```

## نکات مهم

1. **نام Container**: در فایل nginx config، از `lead_app` به عنوان نام host استفاده می‌شود
2. **Port**: PHP-FPM روی port 9000 گوش می‌دهد
3. **شبکه**: هر دو container باید در شبکه `nginx_network` باشند
4. **مسیر فایل‌ها**: مطمئن شوید nginx به فایل‌های پروژه دسترسی دارد

## عیب‌یابی

### خطای "502 Bad Gateway"
```bash
# بررسی کنید app container در حال اجرا است
docker ps | grep lead_app

# بررسی دسترسی شبکه
docker exec <nginx_container> ping lead_app

# بررسی log های PHP-FPM
docker-compose logs app
```

### خطای "File not found"
```bash
# مطمئن شوید nginx به مسیر فایل‌ها دسترسی دارد
docker exec <nginx_container> ls -la /var/www/html/lead/public
```

### بررسی تنظیمات nginx
```bash
# تست پیکربندی nginx
docker exec <nginx_container> nginx -t

# reload کردن nginx
docker exec <nginx_container> nginx -s reload
```
