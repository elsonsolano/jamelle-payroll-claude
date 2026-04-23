<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceBadge extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'icon',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function awards(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceBadge::class);
    }
}
