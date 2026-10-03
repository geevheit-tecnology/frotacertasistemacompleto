<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Fine extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'vehicle_id', 'driver_id', 'notice_number', 'occurred_at', 'due_at', 'amount', 'status', 'description', 'file_path'];

    protected $casts = ['occurred_at' => 'date', 'due_at' => 'date', 'amount' => 'decimal:2'];

    public function scopeTenant(Builder $query, string $companyId, ?string $branchId = null): Builder
    {
        $query->where('company_id', $companyId);
        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }
        return $query;
    }
}
