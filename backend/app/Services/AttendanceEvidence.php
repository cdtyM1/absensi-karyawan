<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceEvidence
{
    public static function validate(Request $r, string $mode): string
    {
        if (! config('attendance.require_evidence')) {
            return 'Tanpa bukti (konfigurasi server)';
        }
        $r->validate(['latitude' => 'required|numeric|between:-90,90', 'longitude' => 'required|numeric|between:-180,180', 'accuracy' => 'required|numeric|between:0,100000', 'selfie' => 'required|file|image|mimes:jpg,jpeg,png|max:2048|dimensions:min_width=64,min_height=64,max_width=1920,max_height=1920']);
        $lat = config('attendance.office_latitude');
        $lng = config('attendance.office_longitude');
        if ($mode === 'Kantor' && $lat !== null && $lat !== '' && $lng !== null && $lng !== '') {
            if (! is_numeric($lat) || ! is_numeric($lng) || abs((float) $lat) > 90 || abs((float) $lng) > 180 || config('attendance.office_radius_m') <= 0 || config('attendance.office_max_accuracy_m') <= 0) {
                throw ValidationException::withMessages(['location' => 'Konfigurasi lokasi kantor tidak valid. Hubungi Admin/HR.']);
            }
            if ((float) $r->input('accuracy') > config('attendance.office_max_accuracy_m')) {
                throw ValidationException::withMessages(['location' => 'Akurasi lokasi belum cukup untuk radius kantor. Ambil lokasi ulang atau gunakan HP.']);
            }
            $d = self::distance((float) $lat, (float) $lng, (float) $r->input('latitude'), (float) $r->input('longitude'));
            if ($d > config('attendance.office_radius_m')) {
                throw ValidationException::withMessages(['location' => 'Lokasi berada di luar radius kantor.']);
            }

            return 'Dalam radius kantor';
        }

        return $mode === 'Kantor' ? 'Lokasi tercatat; radius belum diatur' : 'Lokasi lapangan tercatat; perlu pemeriksaan HR';
    }

    public static function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $a = sin(deg2rad($lat2 - $lat1) / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($lng2 - $lng1) / 2) ** 2;

        return 6371000 * 2 * asin(min(1, sqrt($a)));
    }

    public static function store(Request $r, string $prefix, string $note): array
    {
        if (! config('attendance.require_evidence')) {
            return [$prefix.'_location_note' => $note];
        }
        $path = $r->file('selfie')->store('selfies/'.$r->user()->id, 'evidence');

        return [$prefix.'_latitude' => (float) $r->input('latitude'), $prefix.'_longitude' => (float) $r->input('longitude'), $prefix.'_accuracy' => (float) $r->input('accuracy'), $prefix.'_selfie_path' => $path, $prefix.'_location_note' => $note];
    }
}
