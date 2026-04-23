<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceScoreItem extends Model
{
    protected $fillable = [
        'attendance_score_id',
        'dtr_id',
        'work_date',
        'rule_key',
        'description',
        'points',
        'metadata',
    ];

    protected $casts = [
        'work_date' => 'date',
        'metadata'  => 'array',
    ];

    public function attendanceScore(): BelongsTo
    {
        return $this->belongsTo(AttendanceScore::class);
    }

    public function dtr(): BelongsTo
    {
        return $this->belongsTo(Dtr::class);
    }
}
