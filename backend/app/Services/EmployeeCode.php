<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

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

    public static function preview(): ?string
    {
        if (! Schema::hasTable('employee_code_sequences')) {
            return null;
        }
        $last = DB::table('employee_code_sequences')->where('id', 1)->value('last_number');
        if ($last === null) {
            return null;
        }
        return self::format(max((int) $last, self::highestExisting()) + 1);
    }

    // Must be called inside the same transaction that saves the employee.
    public static function allocate(): string
    {
        if (! Schema::hasTable('employee_code_sequences')) {
            throw ValidationException::withMessages(['employee_code' => 'Penomoran karyawan belum siap. Jalankan php artisan migrate pada server, lalu coba lagi.']);
        }
        $sequence = DB::table('employee_code_sequences')->where('id', 1)->lockForUpdate()->first();
        if (! $sequence) {
            throw ValidationException::withMessages(['employee_code' => 'Data penomoran karyawan belum tersedia. Hubungi pengelola aplikasi.']);
        }
        $next = max((int) $sequence->last_number, self::highestExisting()) + 1;
        DB::table('employee_code_sequences')->where('id', 1)->update(['last_number' => $next]);
        return self::format($next);
    }

    private static function format(int $number): string
    {
        return 'a'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }
}
