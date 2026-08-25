<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TireInspection extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'tire_id', 'vehicle_id', 'driver_id', 'position', 'tread_depth_mm', 'pressure_psi', 'wear_pattern', 'notes', 'photo_paths', 'visual_confidence', 'visual_status', 'inspected_at'];

    protected $casts = [
        'tread_depth_mm' => 'decimal:2',
        'pressure_psi' => 'decimal:2',
        'photo_paths' => 'array',
        'visual_confidence' => 'decimal:2',
        'inspected_at' => 'datetime',
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
