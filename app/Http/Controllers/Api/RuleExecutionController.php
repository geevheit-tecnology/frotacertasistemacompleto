<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RuleExecution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RuleExecutionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            RuleExecution::query()
                ->tenant($request->user()->company_id, $request->user()->branch_id)
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
}
