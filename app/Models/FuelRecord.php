<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FuelRecord extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'vehicle_id', 'driver_id', 'supplier_id', 'cost_center_id', 'fuel_type', 'quantity_liters', 'unit_price', 'total_amount', 'odometer_km', 'average_km_per_liter', 'invoice_number', 'filled_at', 'metadata'];

    protected $casts = [
        'quantity_liters' => 'decimal:3',
        'unit_price' => 'decimal:3',
        'total_amount' => 'decimal:2',
        'average_km_per_liter' => 'decimal:2',
        'filled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function scopeTenant(Builder $query, string $companyId, ?string $branchId = null): Builder
    {
        $query->where('company_id', $companyId);
        if ($branchId !== null) $query->where('branch_id', $branchId);
        return $query;
    }
}
