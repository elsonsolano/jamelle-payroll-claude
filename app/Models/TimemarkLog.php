<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimemarkLog extends Model
{
    protected $fillable = [
        'employee_id',
        'device_id',
        'fetched_at',
        'status',
        'records_fetched',
        'notes',
    ];

    protected $casts = [
        'fetched_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
