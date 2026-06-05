# Web Routes Inertia

Semua route utama memakai session/cookie Laravel dan berada di `routes/web.php`.

## Guest

```text
GET  /login
POST /login
GET  /forgot-password
POST /forgot-password
GET  /reset-password/{token}
POST /reset-password
```

## Authenticated

```text
POST /logout
GET  /dashboard
GET  /profile
GET  /payroll
GET  /payrolls/{payroll}/slip
GET  /documents
POST /documents
GET  /documents/{document}/download
DELETE /documents/{document}
GET  /spt-reports
```

## Super Admin

```text
GET    /companies
POST   /companies
PUT    /companies/{company}
DELETE /companies/{company}
PATCH  /companies/{company}/restore

GET    /users
POST   /users
PUT    /users/{user}
DELETE /users/{user}
PATCH  /users/{user}/restore

GET    /salary-components
POST   /salary-components
PUT    /salary-components/{salaryComponent}
DELETE /salary-components/{salaryComponent}
PATCH  /salary-components/{salaryComponent}/restore
```

## Super Admin dan Admin Company

```text
GET    /employees
POST   /employees
PUT    /employees/{employee}
DELETE /employees/{employee}
PATCH  /employees/{employee}/restore
POST   /employees/{employee}/salary-components
DELETE /employee-salary-components/{component}

POST   /payroll-periods
PUT    /payroll-periods/{period}
DELETE /payroll-periods/{period}
POST   /payroll-periods/{period}/generate
PATCH  /payrolls/{payroll}/paid
PATCH  /payrolls/{payroll}/failed
DELETE /payrolls/{payroll}

POST   /spt-reports
PATCH  /spt-reports/{sptReport}
DELETE /spt-reports/{sptReport}
```

## Employee Bulk Import

```text
GET  /employees-import/template
POST /employees-import
```

Akses:

```text
super_admin
admin_company
```

Perilaku:

- `GET /employees-import/template` mengunduh template Excel `.xlsx`.
- `POST /employees-import` menerima file `.xlsx` dari form Inertia.
- Jika header wajib hilang, file ditolak.
- Jika baris tertentu gagal validasi, baris tersebut ditolak dan ditampilkan dalam laporan import.
- Import dibaca per chunk 500 baris agar lebih aman untuk file besar.

## Export Karyawan dan Slip Gaji PDF

```text
GET  /employees-export
GET  /payrolls/{payroll}/slip/download
POST /payrolls/{payroll}/slip/email
```

Akses:

```text
GET /employees-export                    super_admin, admin_company
GET /payrolls/{payroll}/slip/download    super_admin, admin_company, employee sesuai scope
POST /payrolls/{payroll}/slip/email      super_admin, admin_company, employee sesuai scope
```

Perilaku:

- Export karyawan menghasilkan file Excel `.xlsx`.
- Download slip menghasilkan file PDF.
- Kirim slip email mengirim lampiran PDF ke email user karyawan.
- Employee hanya dapat mengakses slip miliknya sendiri.
- Admin Company hanya dapat mengakses slip company miliknya sendiri.

## Employee Document Detail, Preview, Download, Email

Tambahan untuk role employee dan admin:

```text
GET  /documents/{document}
GET  /documents/{document}/preview
GET  /documents/{document}/download
POST /documents/{document}/email
```

Ketentuan akses:

```text
super_admin   : semua dokumen
admin_company : dokumen karyawan pada company sendiri
employee      : hanya dokumen miliknya sendiri
```

Preview hanya tersedia untuk PDF dan image. File selain PDF/image tetap bisa diunduh dan dikirim sebagai lampiran email.
