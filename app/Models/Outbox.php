<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class Outbox extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'outbox';

    protected $fillable = ['event_name', 'aggregate_type', 'aggregate_id', 'payload', 'idempotency_key', 'status', 'attempts', 'available_at', 'locked_at', 'locked_by', 'published_at', 'dead_lettered_at', 'last_error'];

    protected $casts = [
        'payload' => 'array',
        'available_at' => 'datetime',
        'locked_at' => 'datetime',
        'published_at' => 'datetime',
        'dead_lettered_at' => 'datetime',
    ];
}
