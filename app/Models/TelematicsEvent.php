<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TelematicsEvent extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'vehicle_id', 'recorded_at', 'latitude', 'longitude', 'odometer_km', 'hourmeter', 'speed_kmh', 'source', 'payload'];

    protected $casts = ['recorded_at' => 'datetime', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'hourmeter' => 'decimal:2', 'speed_kmh' => 'decimal:2', 'payload' => 'array'];

    public function scopeTenant(Builder $query, string $companyId, ?string $branchId = null): Builder
    {
        $query->where('company_id', $companyId);
        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }
        return $query;
    }
}
