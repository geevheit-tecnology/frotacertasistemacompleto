<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RuleExecution extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'rule_id', 'trigger_event', 'entity', 'entity_id', 'input_payload', 'result_payload', 'status', 'idempotency_key', 'executed_at'];

    protected $casts = [
        'input_payload' => 'array',
        'result_payload' => 'array',
        'executed_at' => 'datetime',
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
