<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RuleExecution;
use App\Support\Auditor;
use App\Workflow\Application\RuleEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RuleExecutionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        return response()->json(
            RuleExecution::query()
                ->tenant($request->user()->company_id, $request->user()->branch_id)
                ->when(isset($data['status']), fn ($query) => $query->where('status', $data['status']))
                ->latest('executed_at')
                ->paginate(25)
        );
    }

    public function show(Request $request, string $ruleExecution): JsonResponse
    {
        return response()->json(
            RuleExecution::query()
                ->tenant($request->user()->company_id, $request->user()->branch_id)
                ->whereKey($ruleExecution)
                ->firstOrFail()
        );
    }

    public function approve(Request $request, Auditor $auditor, RuleEngine $engine, string $ruleExecution): JsonResponse
    {
        $execution = RuleExecution::query()
            ->tenant($request->user()->company_id, $request->user()->branch_id)
            ->whereKey($ruleExecution)
            ->firstOrFail();
        $before = $execution->toArray();

        if ($execution->status !== 'WAITING_APPROVAL') {
            return response()->json(['message' => 'Rule execution is not waiting approval.'], 422);
        }

        $approved = $engine->approve($execution, $request->user()->id);
        $auditor->recordUpdated($request, $approved, $before, 'api.v1.rule-executions.approve');

        return response()->json($approved);
    }

    public function reject(Request $request, Auditor $auditor, string $ruleExecution): JsonResponse
    {
        $execution = RuleExecution::query()
            ->tenant($request->user()->company_id, $request->user()->branch_id)
            ->whereKey($ruleExecution)
            ->firstOrFail();
        $before = $execution->toArray();

        if ($execution->status !== 'WAITING_APPROVAL') {
            return response()->json(['message' => 'Rule execution is not waiting approval.'], 422);
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $result = $execution->result_payload ?? [];
        $result['rejected_by'] = $request->user()->id;
        $result['rejected_at'] = now()->toISOString();
        $result['rejection_reason'] = $data['reason'] ?? null;

        $execution->forceFill([
            'status' => 'REJECTED',
            'result_payload' => $result,
        ])->save();

        $auditor->recordUpdated($request, $execution, $before, 'api.v1.rule-executions.reject');

        return response()->json($execution->refresh());
    }
}
