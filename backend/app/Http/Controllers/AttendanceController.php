<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Services\WorkSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function index(Request $r)
    {
        $now = CarbonImmutable::now('Asia/Jakarta');

        return view('dashboard', ['now' => $now, 'today' => Attendance::where('user_id', $r->user()->id)->whereDate('work_date', $now->toDateString())->first(), 'records' => Attendance::where('user_id', $r->user()->id)->orderByDesc('work_date')->paginate(20)]);
    }

    public function checkIn(Request $r)
    {
        $data = $r->validate(['mode' => 'required|in:Kantor,Lapangan', 'assignment' => 'nullable|required_if:mode,Lapangan|string|max:200']);
        $now = CarbonImmutable::now('Asia/Jakarta');
        if ($now->isSunday()) {
            throw ValidationException::withMessages(['attendance' => 'Minggu adalah hari libur. Penugasan khusus belum didukung pada tahap ini.']);
        }
        DB::transaction(function () use ($r, $data, $now) {
            DB::table('users')->where('id', $r->user()->id)->lockForUpdate()->first();
            if (Attendance::where('user_id', $r->user()->id)->whereDate('work_date', $now->toDateString())->exists()) {
                throw ValidationException::withMessages(['attendance' => 'Absen masuk sudah tercatat hari ini.']);
            }Attendance::create(['user_id' => $r->user()->id, 'work_date' => $now->toDateString(), 'mode' => $data['mode'], 'assignment' => $data['assignment'] ?? null, 'checked_in_at' => $now, ...WorkSchedule::arrival($now)]);
        });

        return back()->with('status', 'Absen masuk tercatat menggunakan waktu server.');
    }

    public function checkOut(Request $r)
    {
        $now = CarbonImmutable::now('Asia/Jakarta');
        DB::transaction(function () use ($r, $now) {
            $record = Attendance::where('user_id', $r->user()->id)->whereDate('work_date', $now->toDateString())->lockForUpdate()->first();
            if (! $record || $record->checked_out_at) {
                throw ValidationException::withMessages(['attendance' => 'Absen masuk belum ada atau absen pulang sudah tercatat.']);
            }$record->update(['checked_out_at' => $now, 'departure_status' => WorkSchedule::departure($now)]);
        });

        return back()->with('status','Absen pulang tercatat menggunakan waktu server.');
    }
}
