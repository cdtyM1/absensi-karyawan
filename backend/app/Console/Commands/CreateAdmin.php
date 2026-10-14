<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'jayahana:create-admin';

    protected $description = 'Buat akun admin melalui input interaktif; tanpa password bawaan.';

    public function handle(): int
    {
        $data = ['name' => $this->ask('Nama admin'), 'email' => strtolower(trim((string) $this->ask('Email admin'))), 'employee_code' => $this->ask('ID karyawan admin'), 'password' => $this->secret('Kata sandi (minimal 12 karakter, huruf besar/kecil dan angka)'), 'password_confirmation' => $this->secret('Ulangi kata sandi')];
        $v = Validator::make($data, ['name' => 'required|string|max:100', 'email' => 'required|email|unique:users', 'employee_code' => 'required|regex:/^[A-Za-z0-9_-]+$/|max:30|unique:users', 'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()]]);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $e) {
                $this->error($e);
            }

return self::FAILURE;
        }$u = new User;
        $u->name = $data['name'];
        $u->email = $data['email'];
        $u->employee_code = $data['employee_code'];
        $u->password = Hash::make($data['password']);
        $u->role = 'admin';
        $u->active = true;
        $u->save();
        $this->info('Admin dibuat.');

        return self::SUCCESS;
    }
}
