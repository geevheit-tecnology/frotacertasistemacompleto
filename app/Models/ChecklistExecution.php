<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistExecution extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'template_id', 'vehicle_id', 'driver_id', 'odometer_km', 'hourmeter', 'latitude', 'longitude', 'performed_at', 'status', 'metadata'];

    protected $casts = [
        'hourmeter' => 'decimal:1',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'performed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(ChecklistResponse::class);
    }

    public function nonConformities(): HasMany
    {
        return $this->hasMany(NonConformity::class);
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
