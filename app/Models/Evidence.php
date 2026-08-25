<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Evidence extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'evidences';

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'entity', 'entity_id', 'type', 'file_name', 'mime_type', 'storage_path', 'metadata'];

    protected $casts = [
        'metadata' => 'array',
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
