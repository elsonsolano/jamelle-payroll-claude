<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DtrLogEvent extends Model
{
    protected $fillable = [
        'dtr_id',
        'employee_id',
        'work_date',
        'event_key',
        'logged_time',
        'submitted_at',
        'source',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'work_date'    => 'date',
        'submitted_at' => 'datetime',
        'metadata'     => 'array',
    ];

    public function dtr(): BelongsTo
    {
        return $this->belongsTo(Dtr::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
