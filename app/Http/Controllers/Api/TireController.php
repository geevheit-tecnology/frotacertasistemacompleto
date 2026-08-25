<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tire;
use App\Models\Vehicle;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TireController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['status' => ['nullable', 'string', 'max:32'], 'vehicle_id' => ['nullable', 'uuid']]);
        $query = Tire::query()->tenant($request->user()->company_id, $request->user()->branch_id);
        if (isset($data['status'])) $query->where('status', $data['status']);
        if (isset($data['vehicle_id'])) $query->where('vehicle_id', $data['vehicle_id']);
        return response()->json($query->orderBy('fire_number')->paginate(25));
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $this->validatedData($request);
        $branchId = $request->user()->branch_id;
        if (isset($data['vehicle_id'])) $branchId = $this->findVehicle($request, $data['vehicle_id'])->branch_id;
        $tire = Tire::create($data + ['group_id' => $request->user()->group_id, 'company_id' => $request->user()->company_id, 'branch_id' => $branchId]);
        $auditor->recordCreated($request, $tire, 'api.v1.tires.store');
        return response()->json($tire, 201);
    }

    public function show(Request $request, string $tire): JsonResponse
    {
        return response()->json($this->findScoped($request, $tire));
    }

    private function findScoped(Request $request, string $id): Tire
    {
        return Tire::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
    }

    private function findVehicle(Request $request, string $id): Vehicle
    {
        return Vehicle::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'fire_number' => ['required', 'string', 'max:80'],
            'manufacturer' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'size' => ['nullable', 'string', 'max:40'],
            'dot' => ['nullable', 'string', 'max:40'],
            'purchased_at' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'initial_km' => ['nullable', 'integer', 'min:0'],
            'vehicle_id' => ['nullable', 'uuid'],
            'position' => ['nullable', 'string', 'max:40'],
            'tread_depth_mm' => ['nullable', 'numeric', 'min:0'],
            'pressure_psi' => ['nullable', 'numeric', 'min:0'],
            'life_cycle' => ['nullable', 'integer', 'min:1'],
            'retread_count' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);
    }
}
