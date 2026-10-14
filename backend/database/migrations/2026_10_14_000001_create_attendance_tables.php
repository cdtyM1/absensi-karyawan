<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('employee_code')->nullable()->unique();
            $t->string('role')->default('employee');
            $t->boolean('active')->default(true);
        });
        Schema::create('attendances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->date('work_date');
            $t->string('mode');
            $t->string('assignment')->nullable();
            $t->dateTime('checked_in_at');
            $t->dateTime('checked_out_at')->nullable();
            $t->string('arrival_status');
            $t->unsignedInteger('late_minutes')->default(0);
            $t->string('departure_status')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['employee_code', 'role', 'active']));
    }
};
