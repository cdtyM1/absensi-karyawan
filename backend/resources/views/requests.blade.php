@extends('layout')
@section('content')
<p><a href="{{ route('dashboard') }}">Kembali ke halaman absensi</a></p>
<h1>Pengajuan saya</h1><div class="card"><h2>Ajukan izin, sakit, cuti atau lembur</h2>
<form method="post" enctype="multipart/form-data" data-request-form action="{{ route('requests.store') }}">
@csrf
<label>Jenis<select name="kind">@foreach(['Izin','Sakit','Cuti','Lembur'] as $kind)<option @selected(old('kind')===$kind)>{{ $kind }}</option>@endforeach</select></label>
<label data-request-scope>Durasi<select name="scope"><option @selected(old('scope')==='Sehari penuh')>Sehari penuh</option><option @selected(old('scope')==='Sebagian hari')>Sebagian hari</option></select></label>
<p>Sakit dan cuti: sehari penuh. Izin: sehari penuh atau sebagian hari. Lembur selalu dihitung berdasarkan jam.</p>
<div class="grid"><label>Tanggal mulai<input type="date" name="start_date" value="{{ old('start_date') }}" required></label><label>Tanggal selesai<input type="date" name="end_date" value="{{ old('end_date') }}" required></label></div>
<div class="grid" data-request-times><label>Jam mulai<input type="time" name="start_time" value="{{ old('start_time') }}"></label><label>Jam selesai<input type="time" name="end_time" value="{{ old('end_time') }}"></label></div>
<p data-request-time-help>Jam wajib untuk izin sebagian hari dan lembur. Untuk lembur melewati tengah malam, pilih tanggal selesai hari berikutnya. Semua waktu dalam WIB.</p>
<label>Alasan / pekerjaan<textarea name="reason" rows="3" maxlength="2000" required>{{ old('reason') }}</textarea></label>
<label>Lampiran foto (opsional)<input type="file" name="attachment" accept="image/jpeg,image/png"><small>JPG/PNG, maksimal 5 MB. Contoh: foto surat dokter. Lampiran hanya dapat dilihat oleh kamu dan Admin/HR.</small></label>
<button>Kirim pengajuan</button>
</form><p><small>Lembur dicatat sebagai durasi yang diajukan/disetujui, belum menghitung uang lembur. Cuti belum menghitung saldo otomatis.</small></p></div>
<h2>Riwayat pengajuan</h2>
@forelse($requests as $item)
<div class="card">@include('request-details')</div>
@empty
<p>Belum ada pengajuan.</p>
@endforelse
{{ $requests->links() }}
<script src="{{ asset('requests.js').'?v='.filemtime(public_path('requests.js')) }}" defer></script>
@endsection
