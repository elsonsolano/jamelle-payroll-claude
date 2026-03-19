<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'address',
        'work_start_time',
        'work_end_time',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function payrollCutoffs(): HasMany
    {
        return $this->hasMany(PayrollCutoff::class);
    }
}
