<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Insight extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'type', 'title', 'description', 'evidence', 'impact', 'probability', 'confidence', 'priority', 'recommendation', 'entity', 'entity_id', 'status'];

    protected $casts = [
        'evidence' => 'array',
        'probability' => 'decimal:2',
        'confidence' => 'decimal:2',
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
