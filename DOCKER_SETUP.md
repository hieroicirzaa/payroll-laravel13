# Docker Setup Payroll Laravel 13 + Inertia

Dokumen ini menjelaskan cara menjalankan project payroll menggunakan Docker tanpa perlu memasang PHP, Composer, PostgreSQL, Nginx, dan Node secara langsung di mesin lokal.

## Service yang tersedia

- `app`: PHP 8.3 FPM berbasis Debian Bookworm, Composer, extension PostgreSQL, GD, ZIP, CURL, dan dependency untuk PDF/Excel.
- `web`: Nginx untuk melayani Laravel dari folder `public`.
- `vite`: Vite development server untuk Inertia + Vue.
- `postgres`: PostgreSQL 16.
- `mailpit`: SMTP lokal untuk uji kirim email slip gaji.
- `queue`: queue worker Laravel.
- `scheduler`: Laravel scheduler runner.

## Perbaikan konfigurasi Docker

Konfigurasi ini sudah disesuaikan agar lebih aman saat first run:

- `vendor` dipisahkan ke Docker named volume `payroll_vendor`, sehingga tidak bergantung pada folder `vendor` di host.
- `node_modules` dipisahkan ke Docker named volume `payroll_node_modules`, sehingga lebih aman untuk Windows/macOS/Linux.
- Entrypoint memakai shared lock di folder `storage/framework` saat `composer install`, sehingga beberapa container tidak memasang Composer dependency secara bersamaan.
- `queue` dan `scheduler` menunggu migrasi database tersedia sebelum berjalan.
- Container menunggu PostgreSQL siap sebelum menjalankan proses Laravel.

## Menjalankan project

Jalankan dari root project:

```bash
docker compose up -d --build
```

Pada start pertama, container `app` akan membuat `.env` dari `.env.docker`, memasang dependency Composer jika folder `vendor` belum ada, membuat `APP_KEY`, dan menyiapkan folder storage Laravel.

Setelah container aktif, jalankan migrasi dan data dummy:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Buka aplikasi:

```text
http://localhost:8000
```

Buka Vite development server:

```text
http://localhost:5173
```

Buka Mailpit untuk melihat email slip gaji saat development:

```text
http://localhost:8025
```

## Akun dummy

```text
Super Admin
email: superadmin@payroll.local
password: Password123!

Admin Company
email: admin.alpha@payroll.local
password: Password123!

Employee
email: employee.alpha@payroll.local
password: Password123!
```

## Perintah harian

Masuk ke container PHP:

```bash
docker compose exec app sh
```

Jalankan migration:

```bash
docker compose exec app php artisan migrate
```

Reset database dan seed ulang:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Build asset production:

```bash
docker compose exec vite npm run build
```

Melihat log:

```bash
docker compose logs -f app
```

Melihat log semua service:

```bash
docker compose logs -f
```

Menghentikan container:

```bash
docker compose down
```

Menghapus database dan dependency volume Docker:

```bash
docker compose down -v
```

Setelah `down -v`, jalankan ulang:

```bash
docker compose up -d --build
docker compose exec app php artisan migrate:fresh --seed
```


## Jika muncul error `vendor/autoload.php` tidak ditemukan

Error ini berarti named volume `payroll_vendor` masih kosong atau dependency Composer belum berhasil masuk ke container. Pada versi ini, dependency Composer sudah disiapkan saat build image dan akan disalin otomatis ke volume `payroll_vendor` saat container pertama kali menyala.

Reset volume lama dan rebuild image dari awal:

```bash
docker compose down -v --remove-orphans
docker builder prune -f
docker compose build --no-cache app queue scheduler vite
docker compose up -d
```

Cek apakah `vendor/autoload.php` sudah ada:

```bash
docker compose exec app test -f vendor/autoload.php && echo Vendor OK
```

Jika belum ada, paksa restore/install dependency di container app:

```bash
docker compose exec app sh -lc "cp -a /opt/payroll/vendor/. vendor/ 2>/dev/null || composer install --prefer-dist --no-interaction --no-progress --optimize-autoloader"
```

Setelah itu jalankan migrasi ulang:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

## Jika muncul error build `target scheduler: failed to solve`

Error tersebut biasanya muncul karena image PHP berbasis Alpine gagal meng-compile extension XML/DOM secara paralel. Versi ini sudah dipindahkan ke base image Debian Bookworm dan daftar extension yang di-build sudah dibuat lebih aman. Jalankan reset build berikut dari root project:

```bash
docker compose down -v --remove-orphans
docker builder prune -f
docker compose build --no-cache app scheduler queue vite
docker compose up -d
```

Setelah semua container aktif, jalankan migrasi ulang:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

## Troubleshooting singkat

Jika halaman Laravel terbuka tetapi asset Vue/Tailwind belum muncul, cek service Vite:

```bash
docker compose logs -f vite
```

Jika dependency terasa bermasalah, reset volume dependency:

```bash
docker compose down -v
docker compose up -d --build
```

Jika database belum berisi tabel, jalankan:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

## Catatan keamanan

File `.env.docker` disediakan hanya untuk development lokal. Untuk staging atau production, jangan gunakan password database default. Ubah `APP_ENV`, `APP_DEBUG`, `APP_URL`, kredensial database, dan konfigurasi SMTP sesuai server yang digunakan.

## Catatan Fix v4: Composer advisory block

Jika muncul error seperti:

```text
Root composer.json requires phpoffice/phpspreadsheet ^4.0 ... affected by security advisories ... policy.advisories.block
```

Versi v4 sudah menambahkan konfigurasi Composer agar advisory tidak memblokir instalasi dependency untuk kebutuhan lokal/demo Docker:

```json
"config": {
    "policy": {
        "advisories": {
            "block": false
        }
    }
}
```

Untuk PowerShell, jangan gunakan `&&` jika muncul error `The token '&&' is not a valid statement separator`. Jalankan command satu per satu.
