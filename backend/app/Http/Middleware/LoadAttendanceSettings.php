<?php

namespace App\Http\Middleware;

use App\Services\AttendanceSettings;
use Closure;
use Illuminate\Http\Request;

class LoadAttendanceSettings
{
    public function handle(Request $request, Closure $next)
    {
        AttendanceSettings::apply();

        return $next($request);
    }
}
