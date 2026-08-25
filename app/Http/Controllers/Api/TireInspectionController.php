<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Intelligence\Application\TireInspectionAlertService;
use App\Models\Tire;
use App\Models\TireInspection;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TireInspectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(TireInspection::query()->tenant($request->user()->company_id, $request->user()->branch_id)->latest('inspected_at')->paginate(25));
    }

    public function store(Request $request, Auditor $auditor, TireInspectionAlertService $alerts): JsonResponse
    {
        $data = $request->validate([
            'tire_id' => ['required', 'uuid'],
            'driver_id' => ['nullable', 'uuid'],
            'position' => ['nullable', 'string', 'max:40'],
            'tread_depth_mm' => ['required', 'numeric', 'min:0'],
            'pressure_psi' => ['nullable', 'numeric', 'min:0'],
            'wear_pattern' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photo_paths' => ['nullable', 'array'],
            'visual_confidence' => ['nullable', 'numeric', 'between:0,1'],
            'visual_status' => ['nullable', 'string', 'max:40'],
            'inspected_at' => ['nullable', 'date'],
        ]);
        $tire = Tire::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($data['tire_id'])->firstOrFail();
        $inspection = TireInspection::create($data + [
            'group_id' => $tire->group_id,
            'company_id' => $tire->company_id,
            'branch_id' => $tire->branch_id,
            'vehicle_id' => $tire->vehicle_id,
            'position' => $data['position'] ?? $tire->position,
            'inspected_at' => $data['inspected_at'] ?? now(),
        ]);
        $tire->fill(['tread_depth_mm' => $inspection->tread_depth_mm, 'pressure_psi' => $inspection->pressure_psi, 'position' => $inspection->position]);
        $tire->save();
        $auditor->recordCreated($request, $inspection, 'api.v1.tire-inspections.store');
        $alerts->sync($tire->refresh(), $inspection);
        return response()->json($inspection, 201);
    }
}
