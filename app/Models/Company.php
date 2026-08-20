<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['group_id', 'legal_name', 'trade_name', 'tax_id'];
}
