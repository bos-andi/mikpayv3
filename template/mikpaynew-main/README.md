# MIKPAY (Laravel)

Aplikasi manajemen MikroTik / hotspot / PPP + billing.

## Repositori

- **SSH:** `git@github.com:bos-andi/mikpaynew.git`
- **HTTPS:** `https://github.com/bos-andi/mikpaynew.git`

Clone contoh:

```bash
git clone git@github.com:bos-andi/mikpaynew.git mikpaynew
cd mikpaynew
```

Dokumentasi deploy ringkas: [DEPLOY.md](DEPLOY.md).

---

## Fitur Utama

- Landing page + autentikasi admin (`login/register`) dan login Super Admin terpisah.
- Manajemen router MikroTik per session: tambah, edit, hapus, connect/disconnect.
- Dashboard router real-time: identity, resource, health, hotspot/PPP summary.
- Modul Hotspot: users, active, hosts, ip binding, cookies, profile, add/generate user.
- Cetak voucher hotspot + QR code (template Blade Laravel, bisa diedit dari Admin).
- Modul PPP: secrets, profiles, active, billing pelanggan, pembayaran, cetak invoice.
- Import/export template Excel billing PPP (PhpSpreadsheet) dengan format tanggal/nominal.
- Keuangan: cashflow, edit/hapus cashflow, laporan keuangan.
- Laporan penjualan, upload logo per session, template editor, business settings.
- Paket langganan + approval pembayaran oleh Super Admin.

## Teknologi

- PHP 8.2+
- Laravel 12
- MySQL / MariaDB
- Nginx + PHP-FPM (production)
- Node.js — **root project:** build asset Vite (`npm run build`)

---

## Setup lokal (ringkas)

```bash
git clone git@github.com:bos-andi/mikpaynew.git mikpaynew
cd mikpaynew
cp .env.example .env
# Sesuaikan DB di .env; samakan APP_URL dengan URL yang dipakai browser (mis. http://127.0.0.1:8015)
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed   # opsional
npm install && npm run build
php artisan serve --port=8015
```

## Deploy ke VPS Ubuntu + Nginx

Contoh target:
- Domain: `example.com`
- Path aplikasi: `/var/www/mikpaynew`
- User web server: `www-data`
- PHP-FPM socket: `/run/php/php8.2-fpm.sock`

### 1) Install dependency server

```bash
sudo apt update
sudo apt install -y nginx mysql-server unzip curl git supervisor
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl
```

Install Composer:

```bash
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

Install Node.js (LTS) + npm:

```bash
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

### 2) Deploy source code

```bash
sudo mkdir -p /var/www/mikpaynew
sudo chown -R $USER:$USER /var/www/mikpaynew
cd /var/www/mikpaynew

git clone git@github.com:bos-andi/mikpaynew.git .
# atau: git clone https://github.com/bos-andi/mikpaynew.git .
```

### 3) Setup database MySQL

```bash
sudo mysql
```

```sql
CREATE DATABASE mikpaynew CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mikpayuser'@'127.0.0.1' IDENTIFIED BY 'GantiPasswordKuat123!';
GRANT ALL PRIVILEGES ON mikpaynew.* TO 'mikpayuser'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

### 4) Konfigurasi Laravel

```bash
cd /var/www/mikpaynew
cp .env.example .env
```

Edit `.env` minimal:

```env
APP_NAME=mikpay
APP_ENV=production
APP_DEBUG=false
# Wajib sama dengan URL yang dipakai pengguna (scheme + host, tanpa slash akhir). Salah isi sering memicu 419 Page Expired pada form.
APP_URL=https://example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mikpaynew
DB_USERNAME=mikpayuser
DB_PASSWORD=GantiPasswordKuat123!

# Optional override MikroTik
# MIKROTIK_API_PORT=8728
# MIKROTIK_TIMEOUT=3
# MIKROTIK_READ_TIMEOUT=45
# MIKROTIK_SSL=false
```

Install dependency, generate key, migrate:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
```

Seed database hanya jika diperlukan (contoh akun awal):

```bash
php artisan db:seed --force
```

Tautkan storage publik (jika belum):

```bash
php artisan storage:link
```

Build asset frontend (Vite — di **root** project):

```bash
npm ci
npm run build
```

Optimasi cache Laravel:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5) Permission folder

```bash
sudo chown -R www-data:www-data /var/www/mikpaynew
sudo find /var/www/mikpaynew -type f -exec chmod 644 {} \;
sudo find /var/www/mikpaynew -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/mikpaynew/storage /var/www/mikpaynew/bootstrap/cache
```

### 6) Konfigurasi Nginx

Buat file: `/etc/nginx/sites-available/mikpaynew`

```nginx
server {
    listen 80;
    server_name example.com www.example.com;

    root /var/www/mikpaynew/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan site:

```bash
sudo ln -s /etc/nginx/sites-available/mikpaynew /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl enable php8.2-fpm nginx
```

### 7) (Opsional tapi direkomendasikan) Queue worker via Supervisor

Buat file `/etc/supervisor/conf.d/mikpaynew-worker.conf`:

```ini
[program:mikpaynew-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/mikpaynew/artisan queue:work --sleep=3 --tries=1 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/mikpaynew/storage/logs/worker.log
stopwaitsecs=3600
```

Apply:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start mikpaynew-worker:*
```

### 8) HTTPS (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d example.com -d www.example.com
```

Setelah HTTPS aktif, perbarui `APP_URL` ke `https://...`, lalu:

```bash
php artisan config:clear
php artisan config:cache
```

## Default akun seeder (ubah setelah login pertama)

- Admin (database / seeder):
  - Username: `mikpay`
  - Password: `1234`

**Super Admin** tidak disimpan di database. Atur di `.env`:

- `SUPERADMIN_USERNAME` (wajib)
- `SUPERADMIN_EMAIL` (opsional, alternatif login)
- `SUPERADMIN_PASSWORD_HASH` (disarankan, bcrypt) **atau** `SUPERADMIN_PASSWORD` (plain, hanya dev jika hash kosong)

Generate hash contoh:

```bash
php -r "echo password_hash('GantiPasswordKuat', PASSWORD_BCRYPT), PHP_EOL;"
```

Login: `https://domain-anda/superadmin/login`

---

## Command maintenance

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan queue:restart
tail -f storage/logs/laravel.log
```

## Troubleshooting cepat

- **403/500 setelah deploy**: cek owner/permission `storage` dan `bootstrap/cache`.
- **Nginx 502**: pastikan `php8.2-fpm` aktif dan socket path sesuai.
- **Asset CSS/JS tidak muncul**: jalankan `npm run build` ulang di **root** repo.
- **419 Page Expired (form Laravel)**: pastikan `APP_URL` cocok dengan URL di browser (termasuk `http` vs `https`, `localhost` vs `127.0.0.1`); `php artisan config:clear` lalu login ulang.
- **Dashboard lambat/timeout ke router**: cek kredensial router, port API MikroTik, firewall, dan nilai `MIKROTIK_TIMEOUT` / `MIKROTIK_READ_TIMEOUT`.
