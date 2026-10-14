<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['work_date' => 'date', 'checked_in_at' => 'datetime', 'checked_out_at' => 'datetime', 'scheduled_start_at' => 'immutable_datetime', 'scheduled_end_at' => 'immutable_datetime', 'tolerance_minutes' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
