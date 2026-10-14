<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $t) {
            $t->unsignedInteger('id')->primary();
            $t->json('data');
            $t->unsignedInteger('version')->default(0);
            $t->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::table('attendances', function (Blueprint $t) {
            $t->dateTime('scheduled_start_at')->nullable();
            $t->dateTime('scheduled_end_at')->nullable();
            $t->unsignedInteger('tolerance_minutes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', fn (Blueprint $t) => $t->dropColumn(['scheduled_start_at', 'scheduled_end_at', 'tolerance_minutes']));
        Schema::dropIfExists('attendance_settings');
    }
};
