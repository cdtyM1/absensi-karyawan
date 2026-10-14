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
Persetujuan penugasan, izin/cuti, jadwal khusus, hari libur nasional dan koreksi audit belum diimplementasikan pada backend. Mode lapangan merekam keterangan tugas, lokasi dan selfie; belum memverifikasi lokasi penugasan. Minggu ditolak. Tidak menghitung lembur/payroll. Logout dan POST menggunakan CSRF Laravel. Karyawan hanya dapat melihat catatan sendiri; HR dapat membaca rekap seluruh karyawan.

Satu record per karyawan per tanggal WIB, masuk/pulang hanya untuk hari berjalan. Toleransi sampai 08.40.00, setelah itu menit keterlambatan dihitung dari 08.30 dan dibulatkan ke atas. Pulang sebelum jadwal ditandai. Ketentuan menit ini perlu persetujuan HR sebelum dipakai untuk payroll. Spreadsheet memakai pembulatan menit terdekat, sehingga rekap produksi kelak harus menggunakan hasil server.

## Dasar dokumentasi
https://laravel.com/docs/12.x/authentication
https://laravel.com/docs/12.x/database
https://laravel.com/docs/12.x/deployment

## GPS dan selfie
Setelah memperbarui kode, jalankan `php artisan migrate` dan `php artisan config:clear`. Data lama tetap tersimpan; kolom bukti untuk data lama kosong.

Bukti wajib secara default pada masuk dan pulang. Klik Ambil lokasi, Buka kamera, Ambil selfie, lalu tombol absen. Izin kamera/lokasi diperlukan. Localhost atau 127.0.0.1 dapat digunakan untuk uji di Mac. Akses dari HP melalui alamat LAN memerlukan HTTPS. Posisi Mac dapat berupa perkiraan Wi-Fi, bukan sensor GPS.

Foto maksimal 2 MiB, JPEG/PNG, dimensi 64–1920 piksel. File privat pada `storage/app/evidence`, bukan pada public. Jangan menjalankan storage:link untuk folder bukti. Akses foto memerlukan sesi login pemilik atau Admin/HR. Cadangkan folder ini bersama database.

Lokasi diambil sekali saat diminta, tidak dilacak terus. Frontend meminta lokasi baru (60 detik) dan foto baru (2 menit). Batas waktu ini adalah pemeriksaan browser; server tidak membuktikan waktu pengambilan, keaslian kamera, liveness, lokasi GPS, atau kecocokan wajah. Data klien dapat dimanipulasi. Ini bukti pendukung untuk HR.

Radius kantor belum aktif sampai `OFFICE_LATITUDE` dan `OFFICE_LONGITUDE` diisi di .env server. Radius default 100 m, akurasi maksimal untuk radius 100 m, dapat diubah dengan `OFFICE_RADIUS_M` dan `OFFICE_MAX_ACCURACY_M`. Lokasi di luar radius atau akurasi yang tidak cukup ditolak untuk mode kantor. Mode lapangan perlu pemeriksaan HR dan belum dikunci ke penugasan. Karyawan masih memilih mode sendiri. Jangan menganggap fitur ini mencegah semua kecurangan.

`ATTENDANCE_REQUIRE_EVIDENCE=false` hanya untuk pengujian/kebijakan pengecualian server. Pengaturan itu mengizinkan absen tanpa bukti dan bukan pilihan yang dapat diubah karyawan.

## Kelola akun
Admin/HR dapat membuka Kelola pada daftar akun untuk mengganti nama/email, reset password, dan menonaktifkan karyawan. Kata sandi lama tidak ditampilkan. Perubahan email/password/status membatalkan sesi lama melalui auth_version (periksa pada request berikutnya), sedangkan sesi admin yang mengedit dirinya sendiri tetap dipertahankan. Akun admin tidak boleh dinonaktifkan/dihapus. Akun karyawan tanpa absensi dapat dihapus dengan konfirmasi ID; akun dengan absensi harus dinonaktifkan agar riwayat dan bukti tetap tersimpan. Setelah git pull, jalankan php artisan migrate dan php artisan config:clear.

Lokasi absensi diambil otomatis saat halaman dibuka dengan izin browser. Saat mengirim, lokasi yang lebih lama dari 60 detik diperbarui otomatis. Tombol Coba lokasi lagi tersedia jika layanan lokasi gagal. Tidak ada pelacakan lokasi terus-menerus.

Jika perangkat memberikan lokasi lama, aplikasi menunggu pembaruan melalui watchPosition hingga 30 detik, lalu menghentikan pengambilan lokasi setelah berhasil/gagal atau halaman ditutup. Lokasi lama tidak diterima. Jika gagal, periksa jam otomatis, layanan lokasi, dan izin browser atau uji pada HP. Pengujian alur browser simulasi: `node --test tests/Browser/location.test.cjs` (Node 22+).
