<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'uuid'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        $query = Vehicle::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);

        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }

        return response()->json($query->orderBy('plate')->paginate(25));
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'uuid'],
            'plate' => ['required', 'string', 'max:16'],
            'fleet_number' => ['nullable', 'string', 'max:40'],
            'manufacturer' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'model_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'current_odometer_km' => ['nullable', 'integer', 'min:0'],
            'current_hourmeter' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        $vehicle = Vehicle::create($data + [
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
            'branch_id' => $data['branch_id'] ?? $request->user()->branch_id,
        ]);

        $auditor->recordCreated($request, $vehicle, 'api.v1.vehicles.store');

        return response()->json($vehicle, 201);
    }

    public function show(Request $request, string $vehicle): JsonResponse
    {
        return response()->json($this->findScoped($request, $vehicle));
    }

    public function update(Request $request, Auditor $auditor, string $vehicle): JsonResponse
    {
        $model = $this->findScoped($request, $vehicle);
        $before = $model->toArray();
        $data = $request->validate([
            'branch_id' => ['nullable', 'uuid'],
            'plate' => ['sometimes', 'string', 'max:16'],
            'fleet_number' => ['nullable', 'string', 'max:40'],
            'manufacturer' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'model_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'current_odometer_km' => ['nullable', 'integer', 'min:0'],
            'current_hourmeter' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        $model->fill($data);
        $model->save();
        $auditor->recordUpdated($request, $model, $before, 'api.v1.vehicles.update');

        return response()->json($model);
    }

    public function destroy(Request $request, Auditor $auditor, string $vehicle): JsonResponse
    {
        $model = $this->findScoped($request, $vehicle);
        $before = $model->toArray();
        $model->delete();
        $auditor->recordDeleted($request, $model, $before, 'api.v1.vehicles.destroy');

        return response()->json(status: 204);
    }

    private function findScoped(Request $request, string $id): Vehicle
    {
        return Vehicle::query()
            ->tenant($request->user()->company_id, $request->user()->branch_id)
            ->whereKey($id)
            ->firstOrFail();
    }
}
