<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class WorkSchedule
{
    public static function arrival(CarbonImmutable $now): array
    {
        $start = self::at($now, config('attendance.start_time'));
        $deadline = $start->addMinutes((int) config('attendance.tolerance_minutes'));

        return ['arrival_status' => $now->greaterThan($deadline) ? 'Terlambat' : ($now->greaterThan($start) ? 'Dalam toleransi' : 'Tepat waktu'), 'late_minutes' => $now->greaterThan($deadline) ? (int) ceil(($now->timestamp - $start->timestamp) / 60) : 0];
    }

    public static function at(CarbonImmutable $date, string $time): CarbonImmutable
    {
        [$hour,$minute] = explode(':', $time);

        return $date->setTime((int) $hour, (int) $minute);
    }

    public static function snapshot(CarbonImmutable $now): array
    {
        return ['scheduled_start_at' => self::at($now, config('attendance.start_time')), 'scheduled_end_at' => self::at($now, config($now->isSaturday() ? 'attendance.saturday_end_time' : 'attendance.weekday_end_time')), 'tolerance_minutes' => (int) config('attendance.tolerance_minutes')];
    }

    public static function departure(CarbonImmutable $now, ?CarbonImmutable $scheduledEnd = null): string
    {
        $end = $scheduledEnd ?? self::at($now, config($now->isSaturday() ? 'attendance.saturday_end_time' : 'attendance.weekday_end_time'));

        return $now->lessThan($end) ? 'Pulang awal' : 'Selesai';
    }
}
