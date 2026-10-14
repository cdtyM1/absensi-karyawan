# Absensi Karyawan Jayahana

Demo interaktif absensi kantor dan lapangan. Buka index.html melalui browser atau GitHub Pages.

## Fitur
- Simulasi absen masuk/pulang dan pencegahan absen ganda.
- Mode kantor/lapangan dengan keterangan tugas.
- Pengajuan izin, sakit, cuti dan simulasi persetujuan HR.
- Rekap contoh dan ekspor CSV contoh.

## Jadwal
Senin–Jumat 08.30–17.00 WIB. Sabtu 08.30–12.30 WIB. Minggu libur. Toleransi 10 menit: setelah 08.40.00 terlambat. Zona Asia/Jakarta.

## Batasan demo
Data disimpan pada browser (localStorage), waktu mengikuti perangkat. Belum ada login, kontrol hak akses HR, database bersama, GPS, selfie, waktu server atau validasi penugasan nyata. Ekspor CSV masih data contoh. Belum siap untuk absensi produksi.

Jangan unggah data karyawan, selfie, kata sandi atau .env ke repository publik.

## Mengaktifkan GitHub Pages
1. Settings → Pages.
2. Build and deployment: Deploy from a branch.
3. Pilih main dan / (root), lalu Save.
4. Tunggu GitHub menampilkan deployment berhasil dan URL website.

## Tahap berikutnya
Laravel + MySQL untuk login, hak akses, database bersama, waktu server, selfie privat, lokasi, penugasan dan audit perubahan. Dapat dipasang pada hosting/VPS yang kompatibel. Migrasi mencakup kode, database, file privat dan konfigurasi server.

Spreadsheet pendukung Jayahana_Absensi_Template.xlsx dibuat terpisah. Belum tersambung otomatis ke aplikasi. Rekap spreadsheet menghitung baris valid, sementara Alpa harus dicatat eksplisit.

## Backend login dan database
Kode Laravel terdapat pada [backend/](backend/README.md). Backend membutuhkan server PHP, tidak berjalan di GitHub Pages. Tahap ini mencakup akun admin/karyawan, absensi menggunakan waktu server, riwayat pribadi dan rekap HR.
