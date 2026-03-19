<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['date', 'name', 'type'];

    protected $casts = [
        'date' => 'date',
    ];

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'regular'              => 'Regular Holiday',
            'special_non_working'  => 'Special Non-Working',
            'special_working'      => 'Special Working',
            default                => $this->type,
        };
    }
}
