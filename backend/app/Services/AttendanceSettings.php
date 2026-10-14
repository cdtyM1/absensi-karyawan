<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendanceSettings
{
    public const KEYS = ['start_time', 'weekday_end_time', 'saturday_end_time', 'tolerance_minutes', 'require_location', 'office_latitude', 'office_longitude', 'office_radius_m', 'office_max_accuracy_m'];

    public static function stored(): ?object
    {
        return Schema::hasTable('attendance_settings') ? DB::table('attendance_settings')->where('id', 1)->first() : null;
    }

    public static function current(): array
    {
        $values = [];
        foreach (self::KEYS as $key) {
            $values[$key] = config('attendance.'.$key);
        }
        $record = self::stored();

        return array_replace($values, $record ? array_intersect_key(json_decode($record->data, true) ?? [], array_flip(self::KEYS)) : []);
    }

    public static function apply(): void
    {
        $record = self::stored();
        if (! $record) {
            return;
        }
        foreach (array_intersect_key(json_decode($record->data, true) ?? [], array_flip(self::KEYS)) as $key => $value) {
            config(['attendance.'.$key => $value]);
        }
    }
}
