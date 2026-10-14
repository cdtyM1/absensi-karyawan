<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Services\EmployeeCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    private function authorizeAdmin(Request $r): void
    {
        abort_unless($r->user()->role === 'admin', 403);
    }

    private function records(Request $r)
    {
        $r->validate(['date' => 'nullable|date_format:Y-m-d']);

        return Attendance::with('user')->when($r->filled('date'), fn ($q) => $q->whereDate('work_date', $r->input('date')))->orderByDesc('work_date')->orderBy('user_id');
    }

    public function index(Request $r)
    {
        $this->authorizeAdmin($r);

        return view('admin', ['records' => $this->records($r)->paginate(30)->withQueryString(), 'users' => User::orderBy('name')->get(), 'nextEmployeeCode' => EmployeeCode::preview()]);
    }

    public function createUser(Request $r)
    {
        $this->authorizeAdmin($r);
        if (is_string($r->input('email'))) {
            $r->merge(['email' => strtolower(trim($r->input('email')))]);
        }$data = $r->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:255|unique:users', 'password' => ['required', 'confirmed', 'string']]);
        $data['password'] = Hash::make($data['password']);
        $user = DB::transaction(function () use ($data) {
            $user = new User;
            $user->name = $data['name'];
            $user->employee_code = EmployeeCode::allocate();
            $user->email = $data['email'];
            $user->password = $data['password'];
            $user->role = 'employee';
            $user->active = true;
            $user->save();
            return $user;
        }, 5);

        return back()->with('status', 'Akun karyawan berhasil dibuat dengan ID '.$user->employee_code.'.');
    }

    public function export(Request $r)
    {
        $this->authorizeAdmin($r);
        $rows = $this->records($r);

        return response()->streamDownload(function () use ($rows) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Tanggal', 'ID karyawan', 'Nama', 'Mode', 'Penugasan', 'Masuk WIB', 'Pulang WIB', 'Status masuk', 'Menit terlambat', 'Status pulang']);
            foreach ($rows->cursor() as $a) {
                $safe = fn ($v) => preg_match('/^[=+@\-\t\r\n]/u', (string) $v) ? "'".$v : $v;
                fputcsv($f, array_map($safe, [$a->work_date->format('Y-m-d'), $a->user->employee_code, $a->user->name, $a->mode, $a->assignment, $a->checked_in_at->format('H:i:s'), $a->checked_out_at?->format('H:i:s'), $a->arrival_status, $a->late_minutes, $a->departure_status]));
            }fclose($f);
        }, 'rekap-absensi.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
