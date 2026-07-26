# Checklist deploy MIKPAY

Ringkasan operasional. Detail lengkap ada di [README.md](README.md).

## Repositori

```text
git@github.com:bos-andi/mikpaynew.git
```

HTTPS:

```text
https://github.com/bos-andi/mikpaynew.git
```

## Sebelum production

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_URL` sama persis dengan URL publik (termasuk `https://`, tanpa slash akhir) — hindari 419 CSRF / cookie sesi
- [ ] `DB_*` dan user database dengan password kuat
- [ ] `php artisan key:generate` (sekali)
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `php artisan migrate --force`
- [ ] Seed hanya jika perlu: `php artisan db:seed --force`
- [ ] `npm ci && npm run build` di **root** repo (Vite)
- [ ] `php artisan storage:link` jika belum
- [ ] Permission: `storage`, `bootstrap/cache` writable oleh PHP-FPM user
- [ ] `php artisan config:cache`, `route:cache`, `view:cache`
- [ ] Nginx `root` mengarah ke `.../public`
- [ ] HTTPS (Certbot)
- [ ] Queue worker (Supervisor) jika pakai antrian

## Setelah deploy

- [ ] Uji login admin dan (jika dipakai) Super Admin
