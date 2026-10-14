# Backend Absensi Jayahana

Laravel 12 / PHP 8.4+ dengan tampilan Blade tanpa build Node. Tahap pertama: login, akun karyawan oleh HR, database bersama, absensi dengan waktu server, akses HR dan ekspor CSV nyata.

## Jalankan lokal
```sh
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan jayahana:create-admin
php artisan serve
```
Buka http://localhost:8000. Akun admin dibuat interaktif tanpa password bawaan. Buat akun karyawan melalui dashboard HR. Jangan gunakan akun admin bersama.

## MySQL dan hosting/VPS
- Install PHP 8.4+ beserta ekstensi Laravel, Composer, MySQL dan HTTPS.
- Jalankan dari checkout `backend/`; document root web server **wajib backend/public**, jangan root repository atau backend.
- Konfigurasi `.env` di server: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domain-absensi`, `SESSION_SECURE_COOKIE=true`, `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT=3306`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- `composer install --no-dev --optimize-autoloader`, `php artisan key:generate` (hanya instalasi baru), `php artisan migrate --force`, `php artisan jayahana:create-admin`, `php artisan optimize`.
- Beri akses tulis hanya pada storage dan bootstrap/cache sesuai user web server. Jangan pakai chmod 777.
- Cadangkan database dan APP_KEY secara privat. Jangan mengganti APP_KEY pada deployment rutin.
- GitHub Pages hanya menjalankan demo di root, bukan backend. Backend belum terpasang di hosting.

## Uji
`php artisan test`

## Batas tahap ini
GPS, selfie, persetujuan penugasan, izin/cuti, jadwal khusus, hari libur nasional dan koreksi audit belum diimplementasikan pada backend. Form mode lapangan hanya merekam keterangan. Minggu ditolak. Tidak menghitung lembur/payroll. Logout dan POST menggunakan CSRF Laravel. Karyawan hanya dapat melihat catatan sendiri; HR dapat membaca rekap seluruh karyawan.

Satu record per karyawan per tanggal WIB, masuk/pulang hanya untuk hari berjalan. Toleransi sampai 08.40.00, setelah itu menit keterlambatan dihitung dari 08.30 dan dibulatkan ke atas. Pulang sebelum jadwal ditandai. Ketentuan menit ini perlu persetujuan HR sebelum dipakai untuk payroll. Spreadsheet memakai pembulatan menit terdekat, sehingga rekap produksi kelak harus menggunakan hasil server.

## Dasar dokumentasi
https://laravel.com/docs/12.x/authentication
https://laravel.com/docs/12.x/database
https://laravel.com/docs/12.x/deployment
