<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class VehicleDocument extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'company_id', 'branch_id', 'vehicle_id', 'document_type', 'issued_at', 'expires_at', 'status', 'responsible_user_id', 'file_path'];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
    ];
}
