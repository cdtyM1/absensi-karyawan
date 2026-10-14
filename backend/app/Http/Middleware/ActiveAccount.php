<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActiveAccount
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()->active || (int) $request->session()->get('auth_version', 1) !== (int) ($request->user()->auth_version ?? 1)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Akun tidak aktif atau telah diperbarui. Silakan login kembali atau hubungi Admin/HR.']);
        }

        return $next($request);
    }
}
