<?php

namespace App\Http\Controllers\Api;

use App\Financial\Application\VehicleCostReportService;
use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleCostReportController extends Controller
{
    public function show(Request $request, VehicleCostReportService $service, string $vehicle): JsonResponse
    {
        $model = Vehicle::query()
            ->tenant($request->user()->company_id, $request->user()->branch_id)
            ->whereKey($vehicle)
            ->firstOrFail();

        return response()->json($service->summarize($model));
    }
}
