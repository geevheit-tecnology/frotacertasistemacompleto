<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderPart extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['work_order_id', 'inventory_part_id', 'quantity', 'unit_cost', 'total_cost'];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function inventoryPart(): BelongsTo
    {
        return $this->belongsTo(InventoryPart::class);
    }
}
