<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'supplier_id', 'vehicle_id', 'work_order_id', 'cost_center_id', 'number', 'series', 'access_key', 'issued_at', 'amount', 'category', 'file_path', 'status', 'flags'];

    protected $casts = [
        'issued_at' => 'date',
        'amount' => 'decimal:2',
        'flags' => 'array',
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
