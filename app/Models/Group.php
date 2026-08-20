<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['name'];
}
