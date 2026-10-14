@extends('layout')
@section('content')
<p><a href="{{ route('admin') }}">Kembali ke Admin / HR</a></p>
<h1>Kelola akun {{ $account->name }}</h1>
<p>ID: {{ $account->employee_code }} · Akses: {{ $account->role==='admin'?'Admin / HR':'Karyawan' }}</p>
<div class="grid"><div class="card">
<h2>Email, nama dan status</h2>
<form method="post" action="{{ route('admin.users.update',$account) }}">
@csrf
@method('PATCH')
<label>Nama<input name="name" value="{{ old('name',$account->name) }}" maxlength="100" required></label>
<label>Email login<input type="email" name="email" value="{{ old('email',$account->email) }}" maxlength="255" required></label>
@if($account->role==='admin')
<input type="hidden" name="active" value="1"><p>Akun admin tetap aktif.</p>
@else
<label>Status<select name="active"><option value="1" @selected((string)old('active',(int)$account->active)==='1')>Aktif</option><option value="0" @selected((string)old('active',(int)$account->active)==='0')>Nonaktif</option></select></label>
@endif
<h2>Reset kata sandi</h2><p>Kosongkan kedua kolom untuk mempertahankan kata sandi sekarang.</p>
<label>Kata sandi baru<input type="password" name="password" autocomplete="new-password"></label>
<label>Ulangi kata sandi baru<input type="password" name="password_confirmation" autocomplete="new-password"></label>
<p>Kata sandi bebas; ulangi dengan nilai yang sama. Kata sandi lama tidak ditampilkan.</p>
<button>Simpan perubahan</button>
</form></div><div class="card"><h2>Hapus akun</h2>
@if($account->role==='admin')
<p>Akun admin tidak dapat dihapus atau dinonaktifkan.</p>
@elseif($hasAttendance)
<p>Akun ini memiliki riwayat absensi atau pengajuan. Pilih status Nonaktif dan Simpan perubahan untuk menghentikan akses sambil mempertahankan riwayatnya.</p>
@else
<p>Penghapusan permanen tersedia karena akun belum memiliki riwayat absensi atau pengajuan.</p>
<form method="post" action="{{ route('admin.users.destroy',$account) }}">
@csrf
@method('DELETE')
<label>Ketik ID {{ $account->employee_code }} untuk konfirmasi<input name="confirm_code" autocomplete="off" required></label><button class="danger">Hapus akun permanen</button>
</form>
@endif
</div></div>
@endsection
