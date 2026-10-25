# JAYAHANA ABSENSI — MASTER BLUEPRINT V1.3.4

**Status:** Baseline desain disetujui; implementasi dan staging belum diaktifkan  
**Tanggal:** 25 Oktober 2026  
**Pemilik sistem:** Jayahana Groups

## Visi
Membangun aplikasi absensi dan HRIS yang mudah digunakan karyawan, modern, nyaman, responsif, serta dapat dikembangkan tanpa merusak fitur atau data lama. Identitas visual **Modern Friendly Premium**: merah-hitam Jayahana, latar terang, kartu lembut, dan animasi ringan. Referensi produk pihak ketiga hanya inspirasi; jangan menyalin aset/desain berhak cipta.

## Prinsip UX
- Employee-first: absensi dalam sedikit langkah; mobile-first untuk Android dan iPhone, tetap mendukung desktop.
- Bahasa Indonesia yang jelas; satu tombol absensi utama berubah sesuai status kehadiran.
- Navigasi karyawan: **Beranda, Riwayat, Pengajuan, Profil**.
- Dahulukan jam WIB, status hari ini, dan tindakan absen; hindari tabel panjang di beranda mobile.
- Alur: pilih absen → informasi izin GPS/kamera → lokasi dan selfie → konfirmasi → simpan ke server → tampilkan hasil dan riwayat.
- Pesan berhasil **hanya** setelah server mengonfirmasi penyimpanan.
- Sediakan loading, empty, error, offline, penolakan izin, dan pemulihan jaringan yang mudah dipahami.
- Animasi ringan dan tidak menghalangi pengguna; aksesibilitas dan kontras terjaga.

## Design Tokens
- Primary: `#B91C1C`
- Dark: `#171717`
- Soft Red: `#FFF1F2`
- Canvas: `#F8FAFC`
- Success: `#16A34A`
- Warning: `#D97706`
- Error: `#DC2626`
- Font: Inter, fallback sans-serif.
- Spacing: 4/8/12/16/24/32 px.
- Radius kartu: 12–20 px.
- Tombol utama: tinggi minimal 48 px, target sentuh minimal 44×44 px.
- Animasi: 150–250 ms; hormati preferensi reduced motion.
- Ukuran huruf, kontras, dan fokus keyboard harus aksesibel.

## Modul
**Karyawan:** Dashboard, Absen Masuk, Absen Pulang, GPS, Selfie, Riwayat, Pengajuan, Profil.

**Admin/HR:** Dashboard, Monitoring Absensi, Manajemen Karyawan, Jadwal, Radius, Pengajuan, Persetujuan, Rekap, Ekspor, Audit Log.

**Pengembangan berikutnya:** Sinkronisasi Google Sheets satu arah serta modul HRIS tambahan setelah disetujui, bukan aktif secara otomatis.

## Aturan Bisnis yang Tidak Boleh Rusak
- Semua absensi Minggu masuk kategori lembur hari libur, berstatus menunggu hingga Admin/HR menyetujui atau menolak.
- Karyawan dapat mengajukan koreksi lupa masuk/pulang maksimal **3 hari kalender**, termasuk akhir pekan/libur, sampai **23:59 WIB pada hari ketiga**. Lewat tenggat hanya Admin/HR.
- Absensi di luar radius kantor **tidak diblokir hanya karena radius**; beri label monitoring. Pekerja lapangan dapat absen dari lokasi kerja sesuai kebijakan dengan koordinat/detail lokasi jika tersedia.
- Foto selfie dikompresi seperlunya, disimpan privat, dan diakses melalui otorisasi yang tepat.
- Semua hak akses, fitur penting, dan riwayat harus terjaga selama redesain. Jangan mengubah aturan bisnis tanpa persetujuan eksplisit dan tes regresi.
- Jangan menaruh kredensial, `.env`, data pribadi, GPS karyawan, atau selfie pada repository publik.

## Arsitektur
- Pertahankan Laravel 12 dan aturan bisnis yang sudah berjalan.
- Pisahkan business logic dari komponen presentasi; komponen UI reusable.
- Prioritaskan Blade, Tailwind CSS dan Alpine.js; Livewire/Filament dievaluasi sesuai kompatibilitas, bukan kewajiban rewrite.
- Integrasi baru modular, aman, dan dapat diuji.
- Root repository berisi demo browser; backend Laravel ada di `backend/`. Status kode hosting harus diaudit tersendiri.

## Preview dan Deployment
1. Audit repository serta Hostinger dengan akses baca-saja.
2. Kembangkan pada branch khusus, jalankan tes, catat commit.
3. Siapkan staging **hanya setelah izin terpisah**, misalnya `preview-absensi.jayahanagroups.my.id`; database, storage, akun sintetis, dan konfigurasi harus terisolasi dari produksi.
4. Nonaktifkan email/pesan keluar ke karyawan asli di staging; lindungi akses staging.
5. Preview dapat direvisi berkali-kali dan diuji di Android, iPhone, desktop.
6. Pemilik menyetujui commit/versi yang tepat. Persetujuan desain **bukan** izin deployment produksi.
7. Sebelum produksi: verifikasi backup, kemampuan restore, rencana migrasi, rollback, dan dampak perubahan; minta izin produksi eksplisit.
8. Deploy versi yang disetujui, smoke test, pastikan integritas data, rollback bila gagal.

## Quality Gates
- Tes Laravel, JavaScript, dan browser lulus sesuai cakupan perubahan.
- Alur masuk/pulang, anti-duplikasi, GPS, selfie, hak akses, pengajuan, persetujuan lembur Minggu, koreksi tiga hari, rekap/ekspor diuji.
- UI responsif, aksesibel; browser/PWA konsisten jika PWA sudah diterapkan.
- Tidak ada data operasional, rahasia, atau rekaman pribadi dalam fixture/log/screenshot publik.
- Kegagalan tes harus dilaporkan, tidak boleh dinyatakan lulus tanpa bukti.

## Standar Kerja ChatGPT + Codex
1. Baca `AGENTS.md` dan `docs/BLUEPRINT.md` sebelum mengubah kode.
2. Audit modul, dependensi, kebijakan akses, dan tes terkait.
3. Buat perubahan kecil, modular, dan dapat dikembalikan.
4. Pertahankan kompatibilitas fitur penting; sertakan tes regresi.
5. Dokumentasikan perubahan, hasil tes, dan alasan desain.
6. Perbarui preview yang sudah disetujui bila staging tersedia.
7. Minta persetujuan khusus sebelum merge yang berdampak, migrasi, atau rilis produksi.

**Aturan final:** Dokumen ini dasar konsistensi proyek. Revisi desain harus terkontrol dan terdokumentasi, tidak diam-diam; perubahan produksi memerlukan izin tersendiri.
