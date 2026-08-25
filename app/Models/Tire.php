<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Tire extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'fire_number', 'manufacturer', 'model', 'size', 'dot', 'purchased_at', 'purchase_cost', 'initial_km', 'vehicle_id', 'position', 'tread_depth_mm', 'pressure_psi', 'life_cycle', 'retread_count', 'status'];

    protected $casts = [
        'purchased_at' => 'date',
        'purchase_cost' => 'decimal:2',
        'tread_depth_mm' => 'decimal:2',
        'pressure_psi' => 'decimal:2',
    ];

    public function scopeTenant(Builder $query, string $companyId, ?string $branchId = null): Builder
    {
        $query->where('company_id', $companyId);
        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }
        return $query;
    }
}
