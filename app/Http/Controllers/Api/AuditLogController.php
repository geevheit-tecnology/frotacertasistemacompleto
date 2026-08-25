<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $this->query($request);

        return response()->json($query->latest('occurred_at')->paginate(50));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $rows = $this->query($request)->latest('occurred_at')->limit(1000)->get();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['occurred_at', 'actor_id', 'entity', 'entity_id', 'action', 'origin', 'ip_address']);
            foreach ($rows as $row) {
                fputcsv($handle, [$row->occurred_at?->toIso8601String(), $row->actor_id, $row->entity, $row->entity_id, $row->action, $row->origin, $row->ip_address]);
            }
            fclose($handle);
        }, 'audit-log.csv', ['Content-Type' => 'text/csv']);
    }

    private function query(Request $request)
    {
        $data = $request->validate([
            'entity' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:80'],
            'actor_id' => ['nullable', 'uuid'],
        ]);
        $query = AuditLog::query()->tenant($request->user()->company_id, $request->user()->branch_id);
        if (isset($data['entity'])) $query->where('entity', $data['entity']);
        if (isset($data['action'])) $query->where('action', $data['action']);
        if (isset($data['actor_id'])) $query->where('actor_id', $data['actor_id']);
        return $query;
    }
}
