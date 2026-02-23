# 🚀 Laravel 12 Deployment Guide for VPS (Ubuntu/Nginx)

This guide provides step-by-step instructions to deploy this Attendance System to a Linux VPS.

## 🛠 Prerequisites

Ensure your VPS has the following installed:

-   **PHP 8.2 or 8.3** (with extensions: bcmath, curl, mbstring, xml, zip, gd)
-   **MySQL 8.0+** or **MariaDB**
-   **Nginx**
-   **Composer**
-   **Node.js & NPM** (LTS version)

---

## 📥 1. Clone or Upload Project

Navigate to your web directory:

```bash
cd /var/www
git clone https://github.com/your-username/your-repo.git web-absensi
cd web-absensi
```

---

## ⚙️ 2. Environment Setup

Copy the environment file and generate the app key:

```bash
cp .env.example .env
nano .env
```

Update these values in `.env`:

-   `APP_ENV=production`
-   `APP_DEBUG=false`
-   `APP_URL=https://your-domain.com`
-   `DB_DATABASE=your_db_name`
-   `DB_USERNAME=your_db_user`
-   `DB_PASSWORD=your_db_password`

Generate key:

```bash
php artisan key:generate
```

---

## 📦 3. Install Dependencies

```bash
# Backend
composer install --optimize-autoloader --no-dev

# Frontend
npm install
npm run build
```

---

## 🗄️ 4. Database & Storage

```bash
php artisan migrate --force
php artisan storage:link
```

---

## 🛡️ 5. Permissions (Critical)

Nginx needs permission to read/write to specific folders:

```bash
sudo chown -R www-data:www-data /var/www/web-absensi
sudo chmod -R 775 /var/www/web-absensi/storage
sudo chmod -R 775 /var/www/web-absensi/bootstrap/cache
```

---

## 🌐 6. Nginx Configuration

Create a new Nginx config file:

```bash
sudo nano /etc/nginx/sites-available/web-absensi
```

Paste this configuration (Adjust `server_name` and `fastcgi_pass` if necessary):

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/web-absensi/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/web-absensi /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 🔒 7. SSL (Required for Camera/GPS)

Camera and Geolocation features **require HTTPS**. Use Certbot:

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

---

## ⚡ 8. Production Optimization

Run these commands after every update:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🛠️ Handy Deployment Script

You can create a script `deploy.sh` to automate updates:

```bash
#!/bin/bash
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
npm install
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "🚀 Deployment Success!"
```

Usage: `sh deploy.sh`
