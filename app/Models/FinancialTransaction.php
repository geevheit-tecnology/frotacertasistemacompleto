<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'vehicle_id', 'work_order_id', 'supplier_id', 'cost_center_id', 'type', 'category', 'description', 'amount', 'due_at', 'paid_at', 'status', 'origin'];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_at' => 'date',
        'paid_at' => 'date',
    ];

    public function scopeTenant(Builder $query, string $companyId, ?string $branchId = null): Builder
    {
        $query->where('company_id', $companyId);
        if ($branchId !== null) $query->where('branch_id', $branchId);
        return $query;
    }
}
