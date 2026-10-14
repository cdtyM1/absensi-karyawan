<?php

namespace App\Http\Controllers;

use App\Services\AttendanceSettings;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AttendanceSettingsController extends Controller
{
    public function edit(Request $r)
    {
        abort_unless($r->user()->role === 'admin', 403);
        $record = AttendanceSettings::stored();

        return view('attendance-settings', ['settings' => AttendanceSettings::current(), 'version' => $record?->version ?? 0, 'record' => $record, 'ready' => Schema::hasTable('attendance_settings')]);
    }

    public function update(Request $r)
    {
        abort_unless($r->user()->role === 'admin', 403);
        if (! Schema::hasTable('attendance_settings')) {
            throw ValidationException::withMessages(['settings' => 'Pengaturan belum siap. Jalankan php artisan migrate pada server.']);
        }
        $data = $r->validate(['start_time' => 'required|date_format:H:i', 'weekday_end_time' => 'required|date_format:H:i', 'saturday_end_time' => 'required|date_format:H:i', 'tolerance_minutes' => 'required|integer|min:0|max:120', 'require_location' => 'required|boolean', 'office_latitude' => 'nullable|numeric|between:-90,90|required_with:office_longitude|required_if:require_location,1', 'office_longitude' => 'nullable|numeric|between:-180,180|required_with:office_latitude|required_if:require_location,1', 'office_radius_m' => 'required|integer|min:10|max:10000', 'office_max_accuracy_m' => 'required|integer|min:1|max:10000', 'version' => 'required|integer|min:0']);
        $start = CarbonImmutable::parse('2000-01-01 '.$data['start_time']);
        foreach (['weekday_end_time', 'saturday_end_time'] as $key) {
            if (CarbonImmutable::parse('2000-01-01 '.$data[$key])->lessThanOrEqualTo($start->addMinutes((int) $data['tolerance_minutes']))) {
                throw ValidationException::withMessages([$key => 'Jam pulang harus sesudah jam masuk dan batas toleransi.']);
            }
        }
        $version = (int) $data['version'];
        unset($data['version']);
        $data['require_location'] = (bool) $data['require_location'];
        foreach (['tolerance_minutes', 'office_radius_m', 'office_max_accuracy_m'] as $key) {
            $data[$key] = (int) $data[$key];
        }
        foreach (['office_latitude', 'office_longitude'] as $key) {
            $data[$key] = isset($data[$key]) ? (float) $data[$key] : null;
        }
        DB::transaction(function () use ($r, $data, $version) {
            DB::table('attendance_settings')->insertOrIgnore(['id' => 1, 'data' => '{}', 'version' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $record = DB::table('attendance_settings')->where('id', 1)->lockForUpdate()->first();
            if ((int) $record->version !== $version) {
                throw ValidationException::withMessages(['version' => 'Pengaturan telah diubah admin lain. Muat ulang halaman sebelum menyimpan.']);
            }
            DB::table('attendance_settings')->where('id', 1)->update(['data' => json_encode($data), 'version' => $version + 1, 'updated_by' => $r->user()->id, 'updated_at' => now()]);
        }, 5);

        return redirect()->route('admin.settings')->with('status', 'Pengaturan disimpan. Berlaku untuk absensi baru; riwayat lama tetap terjaga.');
    }
}
