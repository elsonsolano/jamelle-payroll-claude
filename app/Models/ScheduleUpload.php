<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleUpload extends Model
{
    protected $fillable = [
        'branch_id',
        'uploaded_by',
        'label',
        'ai_response',
        'status',
    ];

    protected $casts = [
        'ai_response' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function dailySchedules(): HasMany
    {
        return $this->hasMany(DailySchedule::class);
    }
}
