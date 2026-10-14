@extends('layout')
@section('content')<h1>Admin / HR</h1><div class="card"><h2>Rekap kehadiran</h2><form method="get" action="{{ route('admin') }}"><label>Tanggal (kosong untuk semua)<input type="date" name="date" value="{{ request('date') }}"></label><button>Tampilkan</button> <a href="{{ route('admin.export',request()->only('date')) }}">Ekspor CSV</a></form><div class="scroll"><table><tr><th>Tanggal</th><th>Karyawan</th><th>Mode / tugas</th><th>Masuk</th><th>Pulang</th><th>Status</th><th>Bukti absensi</th></tr>
@forelse($records as $a)<tr><td>{{ $a->work_date->format('d/m/Y') }}</td><td>{{ $a->user->name }}</td><td>{{ $a->mode }} / {{ $a->assignment }}</td><td>{{ $a->checked_in_at->format('H:i:s') }}</td><td>{{ $a->checked_out_at?->format('H:i:s') ?? 'Belum pulang' }}</td><td>{{ $a->arrival_status }} / {{ $a->departure_status ?? '—' }}</td><td>
@foreach(['in'=>'Masuk','out'=>'Pulang'] as $phase=>$label)@if($a->{$phase.'_selfie_path'})<div><a href="{{ route('attendance.selfie',[$a,$phase]) }}" target="_blank" rel="noopener">Selfie {{ $label }}</a><br>{{ $a->{$phase.'_latitude'} }}, {{ $a->{$phase.'_longitude'} }}<br>Akurasi ±{{ $a->{$phase.'_accuracy'} }} m<br>{{ $a->{$phase.'_location_note'} }}</div>

@endif
 @endforeach
 @if(!$a->in_selfie_path)Data lama: belum ada bukti
@endif
</td></tr>
@empty
<tr><td colspan="7">Belum ada data untuk filter ini.</td></tr>
@endforelse
</table></div>{{ $records->links() }}</div><div class="grid"><div class="card"><h2>Buat akun karyawan</h2><form method="post" action="{{ route('admin.users') }}">
@csrf<label>ID karyawan<input name="employee_code" maxlength="30" value="{{ old('employee_code') }}" required></label><label>Nama<input name="name" maxlength="100" value="{{ old('name') }}" required></label><label>Email<input type="email" name="email" value="{{ old('email') }}" required></label><label>Kata sandi<input type="password" name="password" minlength="12" autocomplete="new-password" required></label><label>Ulangi kata sandi<input type="password" name="password_confirmation" autocomplete="new-password" required></label><p>Minimal 12 karakter, huruf besar/kecil dan angka.</p><button>Buat akun</button></form></div><div class="card"><h2>Daftar akun</h2><table><tr><th>ID</th><th>Nama</th><th>Akses</th><th>Kelola</th></tr>
@foreach($users as $u)<tr><td>{{ $u->employee_code }}</td><td>{{ $u->name }}</td><td>{{ $u->role }} / {{ $u->active?'Aktif':'Nonaktif' }}</td><td><a href="{{ route('admin.users.edit',$u) }}">Kelola</a></td></tr>
@endforeach
</table></div></div>

@endsection
