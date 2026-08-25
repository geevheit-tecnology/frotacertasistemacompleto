<?php

namespace App\Http\Controllers\Api;

use App\Checklist\Application\ChecklistProcessor;
use App\Http\Controllers\Controller;
use App\Models\ChecklistExecution;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistExecutionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            ChecklistExecution::query()
                ->tenant($request->user()->company_id, $request->user()->branch_id)
                ->with(['responses', 'nonConformities'])
                ->latest('performed_at')
                ->paginate(25)
        );
    }

    public function store(Request $request, ChecklistProcessor $processor, Auditor $auditor): JsonResponse
    {
        $data = $request->validate([
            'template_id' => ['nullable', 'uuid'],
            'vehicle_id' => ['required', 'uuid'],
            'driver_id' => ['nullable', 'uuid'],
            'odometer_km' => ['required', 'integer', 'min:0'],
            'hourmeter' => ['nullable', 'numeric', 'min:0'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'performed_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
            'responses' => ['required', 'array', 'min:1'],
            'responses.*.item_key' => ['required', 'string', 'max:120'],
            'responses.*.label' => ['required', 'string', 'max:255'],
            'responses.*.answer' => ['required', 'string', 'max:80'],
            'responses.*.severity' => ['nullable', 'string', 'max:20'],
            'responses.*.notes' => ['nullable', 'string', 'max:1000'],
            'responses.*.evidence_paths' => ['nullable', 'array'],
        ]);
        $vehicle = $this->findVehicle($request, $data['vehicle_id']);
        if (isset($data['driver_id'])) {
            $this->findDriver($request, $data['driver_id']);
        }

        if ((int) $vehicle->current_odometer_km > (int) $data['odometer_km']) {
            return response()->json(['message' => 'Checklist odometer cannot be lower than current vehicle odometer.'], 422);
        }

        $execution = $processor->complete($request->user(), $vehicle, $data);
        $auditor->recordCreated($request, $execution, 'api.v1.checklists.store');

        return response()->json($execution, 201);
    }

    public function show(Request $request, string $checklist): JsonResponse
    {
        return response()->json(
            ChecklistExecution::query()
                ->tenant($request->user()->company_id, $request->user()->branch_id)
                ->with(['responses', 'nonConformities'])
                ->whereKey($checklist)
                ->firstOrFail()
        );
    }

    private function findVehicle(Request $request, string $id): Vehicle
    {
        return Vehicle::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
    }

    private function findDriver(Request $request, string $id): Driver
    {
        return Driver::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
    }
}
