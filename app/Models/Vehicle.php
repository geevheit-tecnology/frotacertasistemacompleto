<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'plate', 'fleet_number', 'manufacturer', 'model', 'model_year', 'current_odometer_km', 'current_hourmeter', 'status'];

    public function scopeTenant(Builder $query, string $companyId, ?string $branchId = null): Builder
    {
        $query->where('company_id', $companyId);

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }
}
