<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OilServiceRecord extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = [
        'group_id',
        'company_id',
        'branch_id',
        'vehicle_id',
        'engine',
        'oil_type',
        'viscosity',
        'manufacturer',
        'service_odometer_km',
        'service_date',
        'interval_km',
        'next_service_odometer_km',
        'supplier',
        'invoice_number',
        'cost_amount',
        'work_order_number',
        'notes',
    ];

    protected $casts = [
        'service_date' => 'date',
        'cost_amount' => 'decimal:2',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function scopeTenant(Builder $query, string $companyId, ?string $branchId = null): Builder
    {
        $query->where('company_id', $companyId);

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }
}
