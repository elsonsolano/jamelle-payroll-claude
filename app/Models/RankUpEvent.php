<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankUpEvent extends Model
{
    protected $fillable = [
        'employee_id',
        'user_id',
        'old_rank_number',
        'old_rank_name',
        'new_rank_number',
        'new_rank_name',
        'points',
        'source',
        'occurred_at',
        'seen_at',
        'shared_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'seen_at' => 'datetime',
        'shared_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
