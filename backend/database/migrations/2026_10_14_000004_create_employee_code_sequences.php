<?php

use App\Services\EmployeeCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_code_sequences', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedBigInteger('last_number');
        });
        DB::table('employee_code_sequences')->insert(['id' => 1, 'last_number' => EmployeeCode::highestExisting()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_code_sequences');
    }
};
