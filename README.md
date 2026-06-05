# Payroll Secure - Laravel 13 + Inertia.js + Vue + Tailwind + PostgreSQL

Starter project ini adalah sistem payroll berbasis Laravel 13, Inertia.js, Vue 3, Tailwind CSS, dan PostgreSQL. Pola frontend bukan SPA API terpisah. Routing utama memakai `routes/web.php`, controller mengembalikan halaman Inertia, dan autentikasi memakai session/cookie Laravel.

## Modul

- Login, logout, forgot password, reset password.
- Role: `super_admin`, `admin_company`, `employee`.
- Dashboard analytics.
- CRUD Company untuk Super Admin.
- CRUD User untuk Super Admin: list super admin, admin company, dan employee user.
- CRUD Karyawan untuk Super Admin dan Admin Company.
- CRUD Komponen Gaji untuk Super Admin.
- Assign komponen gaji ke karyawan.
- Payroll period, generate payroll, status paid/failed, slip gaji.
- File manager private untuk KTP, ijazah, NPWP, kontrak, dan dokumen lain.
- SPT report sederhana dari payroll berstatus paid.
- PostgreSQL migration dan dummy data.

## Prinsip delete

Sistem payroll tidak memakai hard delete untuk master data utama karena riwayat payroll, slip gaji, audit, dan pajak harus tetap konsisten. Karena itu:

- Company: delete berarti `is_active = false`.
- User: delete berarti `is_active = false`.
- Karyawan: delete berarti `status = inactive` dan akun login ikut nonaktif.
- Komponen gaji: delete berarti `is_active = false`.
- Dokumen: hard delete file dan record dokumen.
- Payroll draft/gagal: dapat dihapus.
- Payroll paid: tidak boleh dihapus.

## Instalasi

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Buat database PostgreSQL:

```sql
CREATE DATABASE payroll_system;
```

Atur `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=payroll_system
DB_USERNAME=postgres
DB_PASSWORD=password
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Jalankan migrasi dan dummy data:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
npm run build
php artisan serve
```

Untuk development:

```bash
composer run dev
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

## Struktur halaman Inertia

```text
resources/js/Pages/Auth/Login.vue
resources/js/Pages/Auth/ForgotPassword.vue
resources/js/Pages/Auth/ResetPassword.vue
resources/js/Pages/Dashboard.vue
resources/js/Pages/Companies/Index.vue
resources/js/Pages/Users/Index.vue
resources/js/Pages/Employees/Index.vue
resources/js/Pages/SalaryComponents/Index.vue
resources/js/Pages/Payroll/Index.vue
resources/js/Pages/Payroll/Slip.vue
resources/js/Pages/Documents/Index.vue
resources/js/Pages/SptReports/Index.vue
resources/js/Pages/Profile/Show.vue
```

## Struktur controller Inertia

```text
app/Http/Controllers/Web/Auth/AuthenticatedSessionController.php
app/Http/Controllers/Web/Auth/PasswordResetController.php
app/Http/Controllers/Web/DashboardController.php
app/Http/Controllers/Web/CompanyController.php
app/Http/Controllers/Web/UserManagementController.php
app/Http/Controllers/Web/EmployeeController.php
app/Http/Controllers/Web/SalaryComponentController.php
app/Http/Controllers/Web/PayrollController.php
app/Http/Controllers/Web/DocumentController.php
app/Http/Controllers/Web/SptReportController.php
```

## Catatan keamanan

- Login memakai session Laravel dan CSRF protection.
- Percobaan login dibatasi memakai `RateLimiter`.
- Role access memakai middleware `role`.
- Dokumen karyawan disimpan pada disk private `payroll_private`.
- Data sensitif seperti `nik`, `npwp`, `bank_account_number`, dan `tax_number` memakai encrypted cast Laravel.
- Password menggunakan hashing Laravel.
- Akun inactive otomatis dikeluarkan oleh middleware `EnsureActiveUser`.

## Catatan pajak

Modul SPT dan pajak di starter ini masih bersifat struktur awal. Rumus pajak memakai placeholder sederhana pada `PayrollCalculator`. Untuk produksi, modul PPh 21 harus divalidasi dengan regulasi pajak terbaru dan kebutuhan perusahaan.

## Dark / Light Theme Toggle

Versi ini sudah memiliki tombol ganti tema gelap dan cerah.

File penting:

- `tailwind.config.js` menggunakan `darkMode: 'class'`.
- `resources/js/Composables/useTheme.js` menyimpan preferensi tema di `localStorage` dengan key `payroll-theme`.
- `resources/js/Components/ThemeToggle.vue` adalah tombol toggle tema.
- `resources/js/Layouts/AppLayout.vue` menampilkan tombol tema di header setelah login.
- Halaman auth juga menampilkan tombol tema di kanan atas.

Perilaku tema:

- Jika user sudah memilih tema, sistem memakai preferensi dari `localStorage`.
- Jika belum ada preferensi, sistem mengikuti preferensi perangkat/browser.
- Tema diterapkan ke elemen `<html>` melalui class `dark`.

## Fitur Baru: Bulk Import Karyawan via Excel

Halaman `Karyawan` sekarang mendukung pembuatan banyak data karyawan sekaligus melalui file Excel `.xlsx`.

Alur penggunaan:

1. Login sebagai `super_admin` atau `admin_company`.
2. Buka menu `Karyawan`.
3. Klik `Download Template` untuk mengunduh format Excel resmi.
4. Isi data pada sheet `employee_import` tanpa mengubah nama header pada baris pertama.
5. Drag file Excel ke area import atau klik area upload.
6. Klik `Import Data Karyawan`.
7. Sistem menampilkan jumlah baris diproses, berhasil dibuat, dan baris yang ditolak.

Kolom wajib:

- `company_code`, khusus Super Admin. Admin Company boleh mengosongkan karena company diambil dari akun login.
- `name`
- `email`
- `password`
- `employee_number`
- `position`
- `join_date`
- `employment_status`
- `basic_salary`
- `status`

Validasi format:

- File harus `.xlsx`.
- Header wajib harus tersedia. Jika header wajib tidak ditemukan, file langsung ditolak.
- Sistem mencocokkan header dengan beberapa kata kunci, misalnya `nama`, `nama_karyawan`, atau `name` akan dibaca sebagai `name`.
- `join_date` menggunakan format `YYYY-MM-DD`.
- `basic_salary` harus angka.
- `status` hanya menerima `active` atau `inactive`.

Implementasi teknis:

- Template dibuat oleh `App\Application\Employees\Imports\EmployeeImportTemplate`.
- Proses import dibuat oleh `App\Application\Employees\Imports\EmployeeBulkImportService`.
- Pembacaan file menggunakan chunk `500` baris melalui `EmployeeImportChunkReadFilter`.
- Library Excel menggunakan `phpoffice/phpspreadsheet`.

Endpoint web:

```text
GET  /employees-import/template
POST /employees-import
```

Catatan keamanan: karena template memuat password awal karyawan, gunakan file ini hanya di perangkat dan jaringan yang aman. Untuk produksi, lebih baik mengembangkan lanjutan berupa password sementara acak dan pengiriman tautan reset password ke email karyawan.

## Fitur Baru: Export Karyawan dan Slip Gaji PDF

Halaman `Karyawan` sekarang memiliki tombol `Export Karyawan`. Export menghasilkan file `.xlsx` berisi data karyawan sesuai scope role login.

- `super_admin` mengekspor seluruh karyawan semua company.
- `admin_company` hanya mengekspor karyawan dari company miliknya.
- Export diproses per chunk 500 data agar lebih aman untuk jumlah karyawan besar.

Halaman `Payroll` dan detail `Slip Gaji` sekarang memiliki tombol:

- `PDF` atau `Download PDF` untuk mengunduh slip gaji dalam format PDF.
- `Email` atau `Kirim Email` untuk mengirim slip gaji PDF ke email karyawan.

Template PDF dibuat menyerupai format slip gaji korporat: header company, identitas karyawan, periode, bulan keberapa, komponen gaji, potongan, total diterima, catatan, dan footer computer-generated statement.

Mail default di `.env.example` menggunakan `MAIL_MAILER=log`. Untuk produksi, ubah konfigurasi `MAIL_*` sesuai SMTP perusahaan.

Endpoint baru:

```text
GET  /employees-export
GET  /payrolls/{payroll}/slip/download
POST /payrolls/{payroll}/slip/email
```

Dependency baru:

```text
dompdf/dompdf
```

## Docker Development

Project ini sudah dilengkapi Docker setup. File yang dipakai:

```text
Dockerfile
docker-compose.yml
.env.docker
docker/nginx/default.conf
docker/php/php.ini
docker/php/entrypoint.sh
DOCKER_SETUP.md
```

Jalankan:

```bash
docker compose up -d --build
docker compose exec app php artisan migrate:fresh --seed
```

Aplikasi berjalan di:

```text
http://localhost:8000
```

Mailpit untuk uji email slip gaji berjalan di:

```text
http://localhost:8025
```

Lihat panduan lengkap di `DOCKER_SETUP.md`.

### Update: Employee Document Detail, Download, and Email

Role `employee` dapat membuka menu `Dokumen`, melihat list dokumen miliknya sendiri, membuka halaman detail dokumen, download file, dan mengirim dokumen tersebut ke email karyawan. List dokumen juga menampilkan informasi bulan unggah dalam format `Bulan ke-N (Nama Bulan) Tahun`.

Route utama:

```text
GET  /documents/{document}
GET  /documents/{document}/preview
GET  /documents/{document}/download
POST /documents/{document}/email
```

Pengiriman email dokumen memakai `App\Mail\EmployeeDocumentMail`. Untuk development Docker, hasil email dapat dicek melalui Mailpit pada `http://localhost:8025`.
