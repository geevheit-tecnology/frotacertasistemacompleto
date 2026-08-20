<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Auditor
{
    public function recordCreated(Request $request, Model $model, string $origin): void
    {
        AuditLog::create([
            'group_id' => $model->getAttribute('group_id'),
            'company_id' => $model->getAttribute('company_id'),
            'branch_id' => $model->getAttribute('branch_id'),
            'actor_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'entity' => $model->getTable(),
            'entity_id' => $model->getKey(),
            'action' => 'created',
            'after_value' => $model->toArray(),
            'origin' => $origin,
        ]);
    }

    /**
     * @param array<string, mixed> $before
     */
    public function recordUpdated(Request $request, Model $model, array $before, string $origin): void
    {
        AuditLog::create([
            'group_id' => $model->getAttribute('group_id'),
            'company_id' => $model->getAttribute('company_id'),
            'branch_id' => $model->getAttribute('branch_id'),
            'actor_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'entity' => $model->getTable(),
            'entity_id' => $model->getKey(),
            'action' => 'updated',
            'before_value' => $before,
            'after_value' => $model->toArray(),
            'origin' => $origin,
        ]);
    }

    /**
     * @param array<string, mixed> $before
     */
    public function recordDeleted(Request $request, Model $model, array $before, string $origin): void
    {
        AuditLog::create([
            'group_id' => $model->getAttribute('group_id'),
            'company_id' => $model->getAttribute('company_id'),
            'branch_id' => $model->getAttribute('branch_id'),
            'actor_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'entity' => $model->getTable(),
            'entity_id' => $model->getKey(),
            'action' => 'deleted',
            'before_value' => $before,
            'origin' => $origin,
        ]);
    }
}
