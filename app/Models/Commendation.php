<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commendation extends Model
{
    protected $fillable = [
        'sender_user_id',
        'recipient_employee_id',
        'trait_ids',
        'points',
    ];

    protected $casts = [
        'trait_ids' => 'array',
        'points' => 'integer',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'recipient_employee_id');
    }
}
