<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class WorkSchedule
{
    public static function arrival(CarbonImmutable $now): array
    {
        $start = $now->setTime(8, 30);
        $deadline = $start->addMinutes(10);

        return ['arrival_status' => $now->greaterThan($deadline) ? 'Terlambat' : ($now->greaterThan($start) ? 'Dalam toleransi' : 'Tepat waktu'), 'late_minutes' => $now->greaterThan($deadline) ? (int) ceil(($now->timestamp - $start->timestamp) / 60) : 0];
    }

    public static function departure(CarbonImmutable $now): string
    {
        return $now->lessThan($now->isSaturday() ? $now->setTime(12, 30) : $now->setTime(17, 0)) ? 'Pulang awal' : 'Selesai';
    }
}
