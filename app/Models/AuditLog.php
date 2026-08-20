<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'actor_id', 'occurred_at', 'ip_address', 'entity', 'entity_id', 'action', 'before_value', 'after_value', 'origin'];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'before_value' => 'array',
            'after_value' => 'array',
        ];
    }
}
