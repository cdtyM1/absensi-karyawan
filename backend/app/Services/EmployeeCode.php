<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmployeeCode
{
    public static function highestExisting(): int
    {
        $highest = 0;
        foreach (User::whereNotNull('employee_code')->pluck('employee_code') as $code) {
            if (preg_match('/^a([0-9]{1,12})$/i', $code, $matches)) {
                $highest = max($highest, (int) $matches[1]);
            }
        }
        return $highest;
    }

    public static function preview(): string
    {
        $last = DB::table('employee_code_sequences')->where('id', 1)->value('last_number');
        return self::format(max((int) $last, self::highestExisting()) + 1);
    }

    // Must be called inside the same transaction that saves the employee.
    public static function allocate(): string
    {
        $sequence = DB::table('employee_code_sequences')->where('id', 1)->lockForUpdate()->first();
        $next = max((int) $sequence->last_number, self::highestExisting()) + 1;
        DB::table('employee_code_sequences')->where('id', 1)->update(['last_number' => $next]);
        return self::format($next);
    }

    private static function format(int $number): string
    {
        return 'a'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }
}
