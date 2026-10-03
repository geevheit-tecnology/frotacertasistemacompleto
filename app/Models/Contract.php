<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'supplier_id', 'number', 'type', 'starts_at', 'ends_at', 'amount', 'status', 'file_path'];

    protected $casts = ['starts_at' => 'date', 'ends_at' => 'date', 'amount' => 'decimal:2'];

    public function scopeTenant(Builder $query, string $companyId, ?string $branchId = null): Builder
    {
        $query->where('company_id', $companyId);
        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }
        return $query;
    }
}
