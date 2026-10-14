@extends('layout')
@section('content')<h1>Halo, {{ auth()->user()->name }}</h1><p>{{ $now->format('d/m/Y H:i:s') }} WIB · waktu server saat halaman dimuat</p><div class="grid"><div class="card"><h2>Absensi hari ini</h2>
@if(!$today)<form method="post" enctype="multipart/form-data" data-attendance-form action="{{ route('attendance.in') }}">
@csrf<label>Mode kerja<select name="mode"><option @selected(old('mode')==='Kantor')>Kantor</option><option @selected(old('mode')==='Lapangan')>Lapangan</option></select></label><label>Penugasan lapangan<input name="assignment" maxlength="200" value="{{ old('assignment') }}" placeholder="Wajib untuk mode lapangan"></label>
@include('evidence')<button data-submit>Absen masuk</button></form>
@else
<p>Masuk: <b>{{ $today->checked_in_at->format('H:i:s') }}</b> · {{ $today->arrival_status }}</p><p>Mode: {{ $today->mode }} · {{ $today->assignment }}</p>
@if(!$today->checked_out_at)<form method="post" enctype="multipart/form-data" data-attendance-form action="{{ route('attendance.out') }}">
@csrf
@include('evidence')<button data-submit>Absen pulang</button></form>
@else
<p>Pulang: <b>{{ $today->checked_out_at->format('H:i:s') }}</b> · {{ $today->departure_status }}</p>

@endif
 
@endif
</div><div class="card"><h2>Jadwal kerja</h2><p>Senin–Jumat {{ config('attendance.start_time') }}–{{ config('attendance.weekday_end_time') }} WIB</p><p>Sabtu {{ config('attendance.start_time') }}–{{ config('attendance.saturday_end_time') }} WIB · Minggu libur</p><p>Toleransi {{ config('attendance.tolerance_minutes') }} menit. Menit terlambat dihitung dari jam masuk setelah melewati toleransi.</p></div></div><div class="card"><h2>Riwayat saya</h2><div class="scroll"><table><tr><th>Tanggal</th><th>Mode</th><th>Masuk</th><th>Pulang</th><th>Status masuk</th><th>Status pulang</th></tr>
@forelse($records as $a)<tr><td>{{ $a->work_date->format('d/m/Y') }}</td><td>{{ $a->mode }}</td><td>{{ $a->checked_in_at->format('H:i:s') }}</td><td>{{ $a->checked_out_at?->format('H:i:s') ?? 'Belum pulang' }}</td><td>{{ $a->arrival_status }}</td><td>{{ $a->departure_status ?? '—' }}<br>
@if($a->in_selfie_path)<a class="evidence-tag evidence-tag-in" aria-label="Lihat foto absen masuk" target="_blank" rel="noopener" href="{{ route('attendance.selfie',[$a,'in']) }}">IN</a>

@endif
 @if($a->out_selfie_path)<a class="evidence-tag evidence-tag-out" aria-label="Lihat foto absen pulang" target="_blank" rel="noopener" href="{{ route('attendance.selfie',[$a,'out']) }}">OUT</a>

@endif
</td></tr>
@empty
<tr><td colspan="6">Belum ada absensi.</td></tr>
@endforelse
</table></div>{{ $records->links() }}</div><script src="{{ asset('attendance.js').'?v='.filemtime(public_path('attendance.js')) }}" defer></script>

@endsection
