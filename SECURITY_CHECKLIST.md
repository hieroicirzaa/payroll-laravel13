# Security Checklist

## Sudah diterapkan dalam starter

- Password memakai Laravel hashing.
- Access token memakai Sanctum personal access token.
- Refresh token dirotasi dan disimpan dalam bentuk hash.
- Token refresh punya waktu kedaluwarsa.
- Role middleware untuk super admin, admin company, dan employee.
- Company scope middleware untuk membatasi akses tenant.
- Dokumen karyawan disimpan di private disk.
- Validasi upload file: pdf, jpg, jpeg, png; maksimal 5 MB.
- Encrypted casts untuk data sensitif.
- Audit log untuk aksi penting.
- Database memakai PostgreSQL.

## Wajib sebelum produksi

- Aktifkan HTTPS.
- Set `APP_DEBUG=false`.
- Gunakan SMTP resmi, bukan `MAIL_MAILER=log`.
- Simpan file di object storage private jika skala besar.
- Tambahkan rate limiter lebih ketat untuk login dan reset password.
- Tambahkan 2FA untuk super admin dan admin company.
- Tambahkan backup terenkripsi untuk database dan dokumen.
- Tambahkan approval workflow untuk payroll paid/failed.
- Validasi rumus pajak PPh 21/SPT dengan regulasi terbaru.
- Tambahkan test untuk authorization, payroll calculation, dan document access.
