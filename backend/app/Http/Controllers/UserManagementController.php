<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    private function authorizeAdmin(Request $r): void
    {
        abort_unless($r->user()->role === 'admin', 403);
    }

    public function edit(Request $r, User $user)
    {
        $this->authorizeAdmin($r);

        return view('manage-user', ['account' => $user, 'hasAttendance' => Attendance::where('user_id', $user->id)->exists()]);
    }

    public function update(Request $r, User $user)
    {
        $this->authorizeAdmin($r);
        if (is_string($r->input('email'))) {
            $r->merge(['email' => Str::lower(trim($r->input('email')))]);
        }
        $data = $r->validate(['name' => 'required|string|max:100', 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)], 'active' => 'required|boolean', 'password' => ['nullable', 'confirmed', Password::min(12)->mixedCase()->numbers()]]);
        if ($user->role === 'admin' && ! $data['active']) {
            throw ValidationException::withMessages(['active' => 'Akun admin harus tetap aktif.']);
        }
        DB::transaction(function () use ($user, $data, $r) {
            $u = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $invalidate = $u->email !== $data['email'] || $u->active != (bool) $data['active'] || ! empty($data['password']);
            $u->name = $data['name'];
            $u->email = $data['email'];
            $u->active = (bool) $data['active'];
            if (! empty($data['password'])) {
                $u->password = Hash::make($data['password']);
            }if ($invalidate) {
                $u->auth_version++;
                $u->remember_token = Str::random(60);
            }$u->save();
            if ($u->id === $r->user()->id) {
                $r->session()->put('auth_version', $u->auth_version);
            }
        });

        return redirect()->route('admin.users.edit', $user)->with('status', 'Akun diperbarui. Perubahan email, kata sandi, atau status mengharuskan sesi akun tersebut login ulang.');
    }

    public function destroy(Request $r, User $user)
    {
        $this->authorizeAdmin($r);
        $r->validate(['confirm_code' => ['required', Rule::in([$user->employee_code])]]);
        if ($user->role === 'admin' || $user->id === $r->user()->id) {
            throw ValidationException::withMessages(['delete' => 'Akun admin tidak dapat dihapus.']);
        }
        DB::transaction(function () use ($user) {
            $u = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (Attendance::where('user_id', $u->id)->exists()) {
                throw ValidationException::withMessages(['delete' => 'Akun memiliki riwayat absensi. Nonaktifkan akun agar riwayat tetap tersimpan.']);
            }DB::table('sessions')->where('user_id', $u->id)->delete();
            $u->delete();
        });

        return redirect()->route('admin')->with('status','Akun tanpa riwayat absensi berhasil dihapus.');
    }
}
