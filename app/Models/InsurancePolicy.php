<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InsurancePolicy extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'vehicle_id', 'policy_number', 'provider', 'starts_at', 'expires_at', 'premium_amount', 'status', 'file_path'];

    protected $casts = ['starts_at' => 'date', 'expires_at' => 'date', 'premium_amount' => 'decimal:2'];

    public function scopeTenant(Builder $query, string $companyId, ?string $branchId = null): Builder
    {
        $query->where('company_id', $companyId);
        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }
        return $query;
    }
}
