@extends('layout')
@section('content')
<p><a href="{{ route('admin') }}">Kembali ke Admin / HR</a></p><h1>Persetujuan pengajuan</h1>
<form method="get" action="{{ route('requests.admin') }}"><label>Status<select name="status"><option value="">Semua</option>@foreach(['Menunggu','Disetujui','Ditolak'] as $status)<option @selected(request('status')===$status)>{{ $status }}</option>@endforeach</select></label><button>Tampilkan</button></form>
@forelse($requests as $item)
<div class="card"><h2>{{ $item->user->name }} · {{ $item->user->employee_code }}</h2>
@include('request-details')
@if($item->status==='Menunggu')
@if($item->user_id===auth()->id())
<p>Pengajuan sendiri harus diperiksa admin lain.</p>
@else
<form method="post" action="{{ route('requests.review',$item) }}">
@csrf
@method('PATCH')
<label>Catatan keputusan<textarea name="review_note" rows="2" maxlength="1000" required></textarea></label>
<button name="status" value="Disetujui">Setujui</button> <button class="danger" name="status" value="Ditolak">Tolak</button>
</form>
@endif
@endif
</div>
@empty
<p>Tidak ada pengajuan untuk filter ini.</p>
@endforelse
{{ $requests->links() }}
@endsection
