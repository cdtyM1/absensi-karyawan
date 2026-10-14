@extends('layout')
@section('content')
<p><a href="{{ route('admin') }}">Kembali ke Admin / HR</a></p><h1>Pengaturan absensi</h1>
@if(!$ready)
<p class="errors">Pengaturan belum siap. Jalankan php artisan migrate pada server.</p>
@else
<p>Waktu menggunakan WIB. Jadwal baru berlaku untuk absensi masuk berikutnya. Batas pulang karyawan yang sudah absen masuk tetap mengikuti jadwal saat masuk.</p>
<form method="post" action="{{ route('admin.settings.update') }}">
@csrf
@method('PATCH')
<input type="hidden" name="version" value="{{ $version }}">
<div class="card"><h2>Jadwal kerja</h2><div class="grid">
<label>Jam masuk Senin–Sabtu<input type="time" name="start_time" value="{{ old('start_time',$settings['start_time']) }}" required></label>
<label>Jam pulang Senin–Jumat<input type="time" name="weekday_end_time" value="{{ old('weekday_end_time',$settings['weekday_end_time']) }}" required></label>
<label>Jam pulang Sabtu<input type="time" name="saturday_end_time" value="{{ old('saturday_end_time',$settings['saturday_end_time']) }}" required></label>
<label>Toleransi keterlambatan (menit)<input type="number" name="tolerance_minutes" value="{{ old('tolerance_minutes',$settings['tolerance_minutes']) }}" min="0" max="120" required></label>
</div><p>Minggu libur. Jadwal harus selesai pada hari yang sama. Setelah toleransi terlewati, menit terlambat dihitung dari jam masuk.</p></div>
<div class="card"><h2>Lokasi kantor</h2>
<label>GPS<select name="require_location"><option value="0" @selected((string)old('require_location',(int)$settings['require_location'])==='0')>Opsional — selfie saja</option><option value="1" @selected((string)old('require_location',(int)$settings['require_location'])==='1')>Wajib — lokasi dan selfie</option></select></label>
<p>GPS opsional tidak meminta lokasi dan tidak memeriksa radius. Absensi diberi catatan untuk pemeriksaan HR. Selfie tetap mengikuti ketentuan bukti absensi.</p>
<div class="grid"><label>Latitude kantor<input type="number" step="any" name="office_latitude" value="{{ old('office_latitude',$settings['office_latitude']) }}" min="-90" max="90"></label>
<label>Longitude kantor<input type="number" step="any" name="office_longitude" value="{{ old('office_longitude',$settings['office_longitude']) }}" min="-180" max="180"></label>
<label>Radius kantor (meter)<input type="number" name="office_radius_m" value="{{ old('office_radius_m',$settings['office_radius_m']) }}" min="10" max="10000" required></label>
<label>Batas akurasi lokasi (meter)<input type="number" name="office_max_accuracy_m" value="{{ old('office_max_accuracy_m',$settings['office_max_accuracy_m']) }}" min="1" max="10000" required></label></div>
<p>Koordinat wajib diisi berpasangan; wajib jika GPS diaktifkan. Radius hanya diperiksa pada mode Kantor. Lokasi Lapangan dicatat untuk pemeriksaan HR.</p>
<button>Simpan pengaturan</button>
@if($record)
<p><small>Terakhir diperbarui {{ $record->updated_at }} WIB · versi {{ $version }}</small></p>
@endif
</div></form>
@endif
@endsection
