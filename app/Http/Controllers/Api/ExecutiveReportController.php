<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Reports\Application\ExecutiveReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExecutiveReportController extends Controller
{
    public function show(Request $request, ExecutiveReportService $service): JsonResponse
    {
        return response()->json($service->summarize($request->user()->company_id, $request->user()->branch_id));
    }
}
