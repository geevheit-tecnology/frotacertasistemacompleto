<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventoryPart extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'sku', 'name', 'unit', 'quantity_on_hand', 'minimum_quantity', 'average_cost', 'status'];

    protected $casts = [
        'quantity_on_hand' => 'decimal:3',
        'minimum_quantity' => 'decimal:3',
        'average_cost' => 'decimal:2',
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
