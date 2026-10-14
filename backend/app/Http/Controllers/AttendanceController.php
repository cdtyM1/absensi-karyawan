<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Services\AttendanceEvidence;
use App\Services\WorkSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

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
        $note = AttendanceEvidence::validate($r, $data['mode']);
        $evidence = [];
        try {
            DB::transaction(function () use ($r, $data, $now, $note, &$evidence) {
                DB::table('users')->where('id', $r->user()->id)->lockForUpdate()->first();
                if (Attendance::where('user_id', $r->user()->id)->whereDate('work_date', $now->toDateString())->exists()) {
                    throw ValidationException::withMessages(['attendance' => 'Absen masuk sudah tercatat hari ini.']);
                }$evidence = AttendanceEvidence::store($r, 'in', $note);
                Attendance::create(['user_id' => $r->user()->id, 'work_date' => $now->toDateString(), 'mode' => $data['mode'], 'assignment' => $data['assignment'] ?? null, 'checked_in_at' => $now, ...WorkSchedule::arrival($now), ...(Schema::hasColumn('attendances', 'scheduled_end_at') ? WorkSchedule::snapshot($now) : []), ...$evidence]);
            });
        } catch (Throwable $e) {
            if (isset($evidence['in_selfie_path'])) {
                Storage::disk('evidence')->delete($evidence['in_selfie_path']);
            }throw $e;
        }

        return back()->with('status', 'Absen masuk tercatat menggunakan waktu server.');
    }

    public function checkOut(Request $r)
    {
        $now = CarbonImmutable::now('Asia/Jakarta');
        $evidence = [];
        try {
            DB::transaction(function () use ($r, $now, &$evidence) {
                $a = Attendance::where('user_id', $r->user()->id)->whereDate('work_date', $now->toDateString())->lockForUpdate()->first();
                if (! $a || $a->checked_out_at) {
                    throw ValidationException::withMessages(['attendance' => 'Absen masuk belum ada atau absen pulang sudah tercatat.']);
                }$note = AttendanceEvidence::validate($r, $a->mode);
                $evidence = AttendanceEvidence::store($r, 'out', $note);
                $a->update(['checked_out_at' => $now, 'departure_status' => WorkSchedule::departure($now, $a->scheduled_end_at ?? ($now->isSaturday() ? $now->setTime(12, 30) : $now->setTime(17, 0))), ...$evidence]);
            });
        } catch (Throwable $e) {
            if (isset($evidence['out_selfie_path'])) {
                Storage::disk('evidence')->delete($evidence['out_selfie_path']);
            }throw $e;
        }

        return back()->with('status', 'Absen pulang tercatat menggunakan waktu server.');
    }

    public function selfie(Request $r, Attendance $attendance, string $phase)
    {
        abort_unless($r->user()->role === 'admin' || $r->user()->id === $attendance->user_id, 403);
        abort_unless(in_array($phase, ['in', 'out'], true), 404);
        $path = $attendance->{$phase.'_selfie_path'};
        abort_unless($path && Storage::disk('evidence')->exists($path), 404);

        return response()->file(Storage::disk('evidence')->path($path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
