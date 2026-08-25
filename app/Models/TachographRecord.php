<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TachographRecord extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'vehicle_id', 'number', 'manufacturer', 'model', 'installed_at', 'calibrated_at', 'certificate_number', 'expires_at', 'last_maintenance_at', 'history', 'status'];

    protected $casts = [
        'installed_at' => 'date',
        'calibrated_at' => 'date',
        'expires_at' => 'date',
        'last_maintenance_at' => 'date',
        'history' => 'array',
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
