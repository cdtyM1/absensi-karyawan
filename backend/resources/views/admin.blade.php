@extends('layout')
@section('content')<h1>Admin / HR</h1><p><a href="{{ route('admin.settings') }}">Pengaturan absensi</a></p><p><a href="{{ route('requests.admin') }}">Persetujuan izin, sakit, cuti &amp; lembur</a></p><div class="card"><h2>Rekap kehadiran</h2><form method="get" action="{{ route('admin') }}"><label>Tanggal (kosong untuk semua)<input type="date" name="date" value="{{ request('date') }}"></label><button>Tampilkan</button> <a href="{{ route('admin.export',request()->only('date')) }}">Ekspor CSV</a></form><div class="scroll"><table><tr><th>Tanggal</th><th>Karyawan</th><th>Mode / tugas</th><th>Masuk</th><th>Pulang</th><th>Status</th><th>Bukti absensi</th></tr>
@forelse($records as $a)<tr><td>{{ $a->work_date->format('d/m/Y') }}</td><td>{{ $a->user->name }}</td><td>{{ $a->mode }} / {{ $a->assignment }}</td><td>{{ $a->checked_in_at->format('H:i:s') }}</td><td>{{ $a->checked_out_at?->format('H:i:s') ?? 'Belum pulang' }}</td><td>{{ $a->arrival_status }} / {{ $a->departure_status ?? '—' }}</td><td>
@foreach(['in'=>'Masuk','out'=>'Pulang'] as $phase=>$label)@if($a->{$phase.'_selfie_path'})<div><a class="evidence-tag evidence-tag-{{ $phase }}" aria-label="Lihat foto absen {{ $label }}" href="{{ route('attendance.selfie',[$a,$phase]) }}" target="_blank" rel="noopener">{{ $phase==='in'?'IN':'OUT' }}</a><br>@if($a->{$phase.'_latitude'} !== null){{ $a->{$phase.'_latitude'} }}, {{ $a->{$phase.'_longitude'} }}<br>Akurasi ±{{ $a->{$phase.'_accuracy'} }} m<br>@endif{{ $a->{$phase.'_location_note'} }}</div>

@endif
 @endforeach
 @if(!$a->in_selfie_path)Data lama: belum ada bukti
@endif
</td></tr>
@empty
<tr><td colspan="7">Belum ada data untuk filter ini.</td></tr>
@endforelse
</table></div>{{ $records->links() }}</div>
<div class="card"><h2>Rekap pengajuan disetujui</h2><p>Mengikuti filter tanggal di atas. Pengajuan menunggu dan ditolak tersedia pada halaman persetujuan.</p>
@if($approvedRequests === null)
<p>Modul pengajuan belum siap. Jalankan php artisan migrate pada server.</p>
@else
@forelse($approvedRequests as $item)
<div class="approved-request"><strong>{{ $item->user->name }} · {{ $item->user->employee_code }}</strong><br>@include('request-details')</div>
@empty
<p>Belum ada pengajuan disetujui untuk filter ini.</p>
@endforelse
{{ $approvedRequests->links() }}
@endif
</div><div class="grid"><div class="card"><h2>Buat akun karyawan</h2><form method="post" action="{{ route('admin.users') }}">
@csrf<label>ID karyawan<input placeholder="Penomoran belum siap" value="{{ $nextEmployeeCode }}" readonly aria-describedby="employee-code-help"><small id="employee-code-help">@if($nextEmployeeCode === null)Penomoran belum siap. Jalankan php artisan migrate pada server.@else ID otomatis saat akun disimpan. Nomor dapat berubah jika admin lain membuat akun terlebih dahulu.@endif</small></label><label>Nama<input name="name" maxlength="100" value="{{ old('name') }}" required></label><label>Email<input type="email" name="email" value="{{ old('email') }}" required></label><label>Kata sandi<input type="password" name="password" autocomplete="new-password" required></label><label>Ulangi kata sandi<input type="password" name="password_confirmation" autocomplete="new-password" required></label><p>Kata sandi bebas; ulangi dengan nilai yang sama.</p><button>Buat akun</button></form></div><div class="card" data-account-list><h2>Daftar akun</h2>
<label for="account-search">Cari akun</label><input id="account-search" type="search" placeholder="Cari ID, nama, atau akses…" autocomplete="off" data-account-search aria-controls="account-table">
<p data-account-count role="status" aria-live="polite">{{ $users->count() }} akun</p>
<div class="account-scroll" tabindex="0" role="region" aria-label="Daftar akun karyawan"><table id="account-table"><thead><tr><th>ID</th><th>Nama</th><th>Akses</th><th>Kelola</th></tr></thead><tbody>
@foreach($users as $u)
<tr data-account-row><td>{{ $u->employee_code }}</td><td>{{ $u->name }}</td><td>{{ $u->role }} / {{ $u->active?'Aktif':'Nonaktif' }}</td><td><a href="{{ route('admin.users.edit',$u) }}">Kelola</a></td></tr>
@endforeach
<tr data-account-empty @if($users->isNotEmpty()) hidden @endif><td colspan="4">Tidak ada akun yang cocok.</td></tr>
</tbody></table></div></div></div>
<script src="{{ asset('accounts.js').'?v='.filemtime(public_path('accounts.js')) }}" defer></script>
@endsection
