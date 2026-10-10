<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesTenantBranch;
use App\Http\Controllers\Controller;
use App\Models\MaintenancePlan;
use App\Models\Vehicle;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenancePlanController extends Controller
{
    use ResolvesTenantBranch;

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'component' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'max:32'],
            'branch_id' => ['nullable', 'uuid'],
        ]);
        $branchId = $this->resolveBranchId($request->user(), $data['branch_id'] ?? null);

        $query = MaintenancePlan::query()->tenant($request->user()->company_id, $branchId);

        if (isset($data['component'])) {
            $query->where('component', $data['component']);
        }
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }

        return response()->json($query->latest()->paginate(25));
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $this->validatedData($request);
        $branchId = $request->user()->branch_id;

        if (isset($data['vehicle_id'])) {
            $vehicle = $this->findVehicle($request, $data['vehicle_id']);
            $branchId = $vehicle->branch_id;
        }

        if (! isset($data['interval_km']) && ! isset($data['interval_days']) && ! isset($data['interval_hours'])) {
            return response()->json(['message' => 'At least one preventive interval must be configured.'], 422);
        }

        $plan = MaintenancePlan::create($data + [
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
            'branch_id' => $branchId,
        ]);

        $auditor->recordCreated($request, $plan, 'api.v1.maintenance-plans.store');

        return response()->json($plan, 201);
    }

    public function show(Request $request, string $maintenancePlan): JsonResponse
    {
        return response()->json($this->findScoped($request, $maintenancePlan));
    }

    public function update(Request $request, Auditor $auditor, string $maintenancePlan): JsonResponse
    {
        $plan = $this->findScoped($request, $maintenancePlan);
        $before = $plan->toArray();
        $data = $this->validatedData($request, true);

        if (isset($data['vehicle_id'])) {
            $vehicle = $this->findVehicle($request, $data['vehicle_id']);
            $data['branch_id'] = $vehicle->branch_id;
        }

        $plan->fill($data);
        $plan->save();
        $auditor->recordUpdated($request, $plan, $before, 'api.v1.maintenance-plans.update');

        return response()->json($plan);
    }

    public function destroy(Request $request, Auditor $auditor, string $maintenancePlan): JsonResponse
    {
        $plan = $this->findScoped($request, $maintenancePlan);
        $before = $plan->toArray();
        $plan->delete();
        $auditor->recordDeleted($request, $plan, $before, 'api.v1.maintenance-plans.destroy');

        return response()->json(status: 204);
    }

    private function findScoped(Request $request, string $id): MaintenancePlan
    {
        return MaintenancePlan::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
    }

    private function findVehicle(Request $request, string $id): Vehicle
    {
        return Vehicle::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:160'],
            'component' => [$partial ? 'sometimes' : 'required', 'string', 'max:80'],
            'scope_type' => ['nullable', 'string', 'max:40'],
            'vehicle_id' => ['nullable', 'uuid'],
            'manufacturer' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'interval_km' => ['nullable', 'integer', 'min:1'],
            'interval_days' => ['nullable', 'integer', 'min:1'],
            'interval_hours' => ['nullable', 'numeric', 'min:0.1'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);
    }
}
