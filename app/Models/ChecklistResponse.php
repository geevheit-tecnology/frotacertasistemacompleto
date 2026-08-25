<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class ChecklistResponse extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['checklist_execution_id', 'item_key', 'label', 'answer', 'severity', 'notes', 'evidence_paths'];

    protected $casts = ['evidence_paths' => 'array'];
}
