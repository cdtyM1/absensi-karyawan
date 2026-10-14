<strong>{{ $item->kind }} · {{ $item->scope }}</strong><br>
@if($item->scope==='Sehari penuh')
{{ $item->starts_at->format('d/m/Y') }} – {{ $item->ends_at->subDay()->format('d/m/Y') }}
@else
{{ $item->starts_at->format('d/m/Y H:i') }} – {{ $item->ends_at->format('d/m/Y H:i') }} WIB
@endif
@if($item->kind==='Lembur')
<br>Durasi diajukan: {{ intdiv($item->duration_minutes,60) }} jam {{ $item->duration_minutes%60 }} menit
@endif
<p class="request-reason">{{ $item->reason }}</p>
@if($item->attachment_path)
<p><a href="{{ route('requests.attachment', $item) }}" target="_blank" rel="noopener">Lihat lampiran foto</a></p>
@endif
<span class="request-status">{{ $item->status }}</span>
@if($item->reviewed_at)
<p>Catatan: {{ $item->review_note }}<br><small>Diproses {{ $item->reviewer?->name }} · {{ $item->reviewed_at->format('d/m/Y H:i') }} WIB</small></p>
@endif
