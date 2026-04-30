<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollCutoff extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'void_reason',
        'finalized_at',
        'has_philhealth',
        'philhealth_partner_cutoff_id',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'end_date'      => 'date',
        'finalized_at'  => 'datetime',
        'has_philhealth'=> 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payrollEntries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class);
    }

    public function philhealthPartnerCutoff(): BelongsTo
    {
        return $this->belongsTo(PayrollCutoff::class, 'philhealth_partner_cutoff_id');
    }
}
