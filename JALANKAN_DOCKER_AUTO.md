# Cara Menjalankan Docker Otomatis

Versi ini sudah dibuat agar `docker compose up -d --build` otomatis menjalankan kebutuhan awal Laravel:

- Composer install
- NPM install
- Build asset Vite ke `public/build/manifest.json`
- Clear cache Laravel
- Migration database
- Seeder database

## Jalankan Normal

```powershell
cd D:\web_portofolio\payroll-laravel13
docker compose up -d --build
```

Setelah selesai, buka:

```text
http://localhost:8000
```

Mailpit:

```text
http://localhost:8025
```

## Jalankan dari Nol / Fresh Install

Gunakan ini kalau masih ada container atau volume lama yang bentrok.

```powershell
cd D:\web_portofolio\payroll-laravel13
```

```powershell
docker compose down -v --remove-orphans
```

```powershell
docker ps -a --filter "name=payroll" --format "{{.Names}}" | ForEach-Object { docker rm -f $_ }
```

```powershell
docker volume ls -q | Where-Object { $_ -like "*payroll*" } | ForEach-Object { docker volume rm $_ }
```

```powershell
docker compose up -d --build
```

## Cek Status

```powershell
docker compose ps
```

Service `setup` akan selesai/exit setelah berhasil menyiapkan project. Itu normal.

## Catatan Penting

Service `vite` tidak otomatis jalan di mode normal, karena asset sudah di-build oleh service `setup`.
Kalau ingin hot reload Vite untuk development, jalankan:

```powershell
docker compose --profile dev up -d vite
```

Kalau hanya ingin menjalankan aplikasi, cukup:

```powershell
docker compose up -d --build
```
