<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'type', 'category', 'entity', 'entity_id', 'severity', 'alert_date', 'predicted_date', 'deadline', 'origin', 'rule', 'message', 'recommendation', 'status', 'assignee_id', 'evidence'];

    protected $casts = [
        'alert_date' => 'datetime',
        'predicted_date' => 'datetime',
        'deadline' => 'datetime',
        'evidence' => 'array',
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
