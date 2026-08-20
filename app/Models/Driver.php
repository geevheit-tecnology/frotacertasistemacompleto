<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'name', 'document_number', 'license_number', 'license_category', 'license_expires_at', 'status'];

    protected function casts(): array
    {
        return ['license_expires_at' => 'date'];
    }

    public function scopeTenant(Builder $query, string $companyId, ?string $branchId = null): Builder
    {
        $query->where('company_id', $companyId);

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }
}
