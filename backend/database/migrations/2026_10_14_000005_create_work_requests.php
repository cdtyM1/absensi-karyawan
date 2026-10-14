<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('kind');
            $t->string('scope');
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->unsignedInteger('duration_minutes')->nullable();
            $t->text('reason');
            $t->string('status')->default('Menunggu');
            $t->foreignId('reviewer_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->text('review_note')->nullable();
            $t->dateTime('reviewed_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_requests');
    }
};
