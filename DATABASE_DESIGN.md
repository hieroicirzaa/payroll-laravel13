# Database Design

## Main tables

- `companies`: data perusahaan tenant.
- `users`: akun login dan role.
- `employees`: profil karyawan.
- `employee_documents`: dokumen private milik karyawan.
- `salary_components`: master komponen gaji.
- `employee_salary_components`: komponen gaji per karyawan.
- `payroll_periods`: periode payroll per company.
- `payrolls`: hasil payroll per karyawan dalam satu periode.
- `payroll_items`: detail komponen payroll.
- `spt_reports`: fondasi laporan pajak/SPT.
- `refresh_tokens`: token refresh yang di-hash.
- `audit_logs`: jejak aksi penting.

## Role model

`super_admin` tidak wajib punya `company_id`.

`admin_company` dan `employee` wajib terkait dengan `company_id`.

## Payroll status

- `draft`: payroll sudah dihitung tetapi belum dibayar.
- `paid`: payroll berhasil dibayar.
- `failed`: payroll gagal diproses.

## Document visibility

- Super admin dapat mengakses semua dokumen.
- Admin company hanya dapat mengakses dokumen karyawan di company yang sama.
- Employee hanya dapat mengakses dokumen miliknya sendiri.
