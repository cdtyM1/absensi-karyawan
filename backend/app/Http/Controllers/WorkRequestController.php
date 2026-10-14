<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class WorkRequestController extends Controller
{
    public function index(Request $r)
    {
        if (! Schema::hasTable('work_requests')) {
            return response()->view('requests-unavailable', [], 503);
        }

        return view('requests', ['requests' => WorkRequest::with('reviewer')->where('user_id', $r->user()->id)->latest()->paginate(20)]);
    }

    public function store(Request $r)
    {
        if (! Schema::hasTable('work_requests')) {
            throw ValidationException::withMessages(['request' => 'Modul belum siap. Jalankan php artisan migrate pada server.']);
        }
        $data = $r->validate(['kind' => 'required|in:Izin,Sakit,Cuti,Lembur', 'scope' => 'required|in:Sehari penuh,Sebagian hari', 'start_date' => 'required|date_format:Y-m-d', 'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date', 'start_time' => 'nullable|date_format:H:i', 'end_time' => 'nullable|date_format:H:i', 'reason' => 'required|string|max:2000']);
        $timed = $data['kind'] === 'Lembur' || $data['scope'] === 'Sebagian hari';
        if ($data['kind'] !== 'Izin' && $data['kind'] !== 'Lembur' && $data['scope'] !== 'Sehari penuh') {
            throw ValidationException::withMessages(['scope' => 'Sakit dan cuti menggunakan rentang hari penuh.']);
        }
        if ($timed) {
            $r->validate(['start_time' => 'required|date_format:H:i', 'end_time' => 'required|date_format:H:i']);
        }
        $start = CarbonImmutable::parse($data['start_date'].' '.($timed ? $data['start_time'] : '00:00'), 'Asia/Jakarta');
        $end = CarbonImmutable::parse($data['end_date'].' '.($timed ? $data['end_time'] : '00:00'), 'Asia/Jakarta');
        if (! $timed) {
            $end = $end->addDay();
        }
        if ($end->lessThanOrEqualTo($start) || ($timed && $start->diffInMinutes($end) > 1440) || (! $timed && $start->diffInDays($end) > 366)) {
            throw ValidationException::withMessages(['end_date' => 'Waktu selesai harus sesudah mulai. Pengajuan per jam maksimal 24 jam; rentang hari maksimal 366 hari.']);
        }
        DB::transaction(function () use ($r, $data, $start, $end, $timed) {
            User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            if (WorkRequest::where('user_id', $r->user()->id)->whereIn('status', ['Menunggu', 'Disetujui'])->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists()) {
                throw ValidationException::withMessages(['start_date' => 'Ada pengajuan menunggu/disetujui pada waktu yang bertumpang tindih.']);
            }
            WorkRequest::create(['user_id' => $r->user()->id, 'kind' => $data['kind'], 'scope' => $timed ? 'Sebagian hari' : 'Sehari penuh', 'starts_at' => $start, 'ends_at' => $end, 'duration_minutes' => $data['kind'] === 'Lembur' ? (int) $start->diffInMinutes($end) : null, 'reason' => $data['reason'], 'status' => 'Menunggu']);
        }, 5);

        return redirect()->route('requests')->with('status', 'Pengajuan dikirim dan menunggu persetujuan Admin/HR.');
    }

    public function admin(Request $r)
    {
        abort_unless($r->user()->role === 'admin', 403);
        if (! Schema::hasTable('work_requests')) {
            return response()->view('requests-unavailable', [], 503);
        }
        $data = $r->validate(['status' => 'nullable|in:Menunggu,Disetujui,Ditolak']);

        return view('request-approvals', ['requests' => WorkRequest::with(['user', 'reviewer'])->when($r->filled('status'), fn ($q) => $q->where('status', $data['status']))->orderByRaw("CASE WHEN status = 'Menunggu' THEN 0 ELSE 1 END")->latest()->paginate(20)->withQueryString()]);
    }

    public function review(Request $r, WorkRequest $workRequest)
    {
        abort_unless($r->user()->role === 'admin', 403);
        abort_if($workRequest->user_id === $r->user()->id, 403, 'Pengajuan sendiri harus diperiksa admin lain.');
        $data = $r->validate(['status' => 'required|in:Disetujui,Ditolak', 'review_note' => 'required|string|max:1000']);
        DB::transaction(function () use ($r, $workRequest, $data) {
            $item = WorkRequest::whereKey($workRequest->id)->lockForUpdate()->firstOrFail();
            if ($item->status !== 'Menunggu') {
                throw ValidationException::withMessages(['status' => 'Pengajuan sudah diproses. Keputusan tidak dapat ditimpa.']);
            }
            $item->update(['status' => $data['status'], 'review_note' => $data['review_note'], 'reviewer_id' => $r->user()->id, 'reviewed_at' => CarbonImmutable::now('Asia/Jakarta')]);
        }, 5);

        return back()->with('status','Keputusan pengajuan berhasil disimpan.');
    }
}
