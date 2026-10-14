<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $t) {
            foreach (['in', 'out'] as $p) {
                $t->decimal($p.'_latitude', 10, 7)->nullable();
                $t->decimal($p.'_longitude', 11, 7)->nullable();
                $t->decimal($p.'_accuracy', 10, 2)->nullable();
                $t->string($p.'_selfie_path')->nullable();
                $t->string($p.'_location_note')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $t) {
            foreach (['in', 'out'] as $p) {
                $t->dropColumn([$p.'_latitude', $p.'_longitude', $p.'_accuracy', $p.'_selfie_path', $p.'_location_note']);
            }
        });
    }
};
