<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'vehicle_id', 'driver_id', 'origin', 'destination', 'starts_at', 'ends_at', 'status', 'metadata'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'metadata' => 'array'];

    public function scopeTenant(Builder $query, string $companyId, ?string $branchId = null): Builder
    {
        $query->where('company_id', $companyId);
        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }
        return $query;
    }
}
