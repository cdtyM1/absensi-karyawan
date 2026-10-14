@extends('layout')
@section('content')
<h1>Modul pengajuan belum siap</h1>
<p>Pengelola aplikasi perlu menjalankan <code>php artisan migrate</code> pada server. Data absensi tetap tersedia.</p>
<a href="{{ route('dashboard') }}">Kembali ke halaman absensi</a>
@endsection
