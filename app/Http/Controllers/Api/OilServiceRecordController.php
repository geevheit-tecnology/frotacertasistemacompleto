<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesTenantBranch;
use App\Http\Controllers\Controller;
use App\Intelligence\Application\OilServiceAlertService;
use App\Models\OilServiceRecord;
use App\Models\Vehicle;
use App\Support\Auditor;
use FleetIntelligence\Intelligence\Application\OilServiceProjectionCalculator;
use FleetIntelligence\Intelligence\Domain\OilServiceInput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OilServiceRecordController extends Controller
{
    use ResolvesTenantBranch;

    public function __construct(
        private readonly OilServiceProjectionCalculator $calculator,
        private readonly OilServiceAlertService $alertService,
    )
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['nullable', 'uuid'],
            'branch_id' => ['nullable', 'uuid'],
        ]);
        $branchId = $this->resolveBranchId($request->user(), $data['branch_id'] ?? null);

        $query = OilServiceRecord::query()
            ->tenant($request->user()->company_id, $branchId)
            ->with('vehicle:id,plate,current_odometer_km');

        if (isset($data['vehicle_id'])) {
            $query->where('vehicle_id', $data['vehicle_id']);
        }

        return response()->json($query->latest('service_date')->paginate(25));
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $this->validatedData($request);
        $vehicle = $this->findVehicle($request, $data['vehicle_id']);

        if ($vehicle->current_odometer_km < $data['service_odometer_km']) {
            return response()->json([
                'message' => 'Vehicle odometer cannot be lower than oil service odometer.',
            ], 422);
        }

        $record = OilServiceRecord::create($data + [
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
            'branch_id' => $vehicle->branch_id,
            'next_service_odometer_km' => $data['service_odometer_km'] + $data['interval_km'],
        ]);

        $auditor->recordCreated($request, $record, 'api.v1.oil-services.store');
        $record->load('vehicle');
        $this->alertService->sync($record, $this->calculateProjection($record));

        return response()->json($this->payload($record), 201);
    }

    public function show(Request $request, string $oilService): JsonResponse
    {
        return response()->json($this->payload($this->findScoped($request, $oilService)->load('vehicle')));
    }

    public function update(Request $request, Auditor $auditor, string $oilService): JsonResponse
    {
        $record = $this->findScoped($request, $oilService);
        $before = $record->toArray();
        $data = $this->validatedData($request, true);

        if (isset($data['vehicle_id'])) {
            $vehicle = $this->findVehicle($request, $data['vehicle_id']);
        } else {
            $vehicle = $record->vehicle;
        }

        $serviceOdometer = $data['service_odometer_km'] ?? $record->service_odometer_km;
        $intervalKm = $data['interval_km'] ?? $record->interval_km;

        if ($vehicle->current_odometer_km < $serviceOdometer) {
            return response()->json([
                'message' => 'Vehicle odometer cannot be lower than oil service odometer.',
            ], 422);
        }

        $record->fill($data + [
            'branch_id' => $vehicle->branch_id,
            'next_service_odometer_km' => $serviceOdometer + $intervalKm,
        ]);
        $record->save();

        $auditor->recordUpdated($request, $record, $before, 'api.v1.oil-services.update');
        $record = $record->refresh()->load('vehicle');
        $this->alertService->sync($record, $this->calculateProjection($record));

        return response()->json($this->payload($record));
    }

    public function destroy(Request $request, Auditor $auditor, string $oilService): JsonResponse
    {
        $record = $this->findScoped($request, $oilService);
        $before = $record->toArray();
        $record->delete();

        $auditor->recordDeleted($request, $record, $before, 'api.v1.oil-services.destroy');

        return response()->json(status: 204);
    }

    private function findScoped(Request $request, string $id): OilServiceRecord
    {
        return OilServiceRecord::query()
            ->tenant($request->user()->company_id, $request->user()->branch_id)
            ->whereKey($id)
            ->firstOrFail();
    }

    private function findVehicle(Request $request, string $id): Vehicle
    {
        return Vehicle::query()
            ->tenant($request->user()->company_id, $request->user()->branch_id)
            ->whereKey($id)
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(OilServiceRecord $record): array
    {
        return [
            'record' => $record,
            'projection' => $this->projection($record),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function projection(OilServiceRecord $record): array
    {
        $vehicle = $record->vehicle;
        $projection = $this->calculateProjection($record);

        return $projection->evidence() + [
            'severity' => $projection->severity->value,
            'message' => $projection->message,
            'recommendation' => $projection->recommendation,
        ];
    }

    private function calculateProjection(OilServiceRecord $record): \FleetIntelligence\Intelligence\Domain\OilServiceProjection
    {
        $vehicle = $record->vehicle;

        return $this->calculator->calculate(new OilServiceInput(
            vehicleId: $vehicle->id,
            vehiclePlate: $vehicle->plate,
            lastServiceKm: $record->service_odometer_km,
            lastServiceDate: \DateTimeImmutable::createFromInterface($record->service_date),
            currentKm: $vehicle->current_odometer_km,
            referenceDate: new \DateTimeImmutable(),
            configuredIntervalKm: $record->interval_km,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'vehicle_id' => [$required, 'uuid'],
            'engine' => ['nullable', 'string', 'max:120'],
            'oil_type' => ['nullable', 'string', 'max:80'],
            'viscosity' => ['nullable', 'string', 'max:40'],
            'manufacturer' => ['nullable', 'string', 'max:120'],
            'service_odometer_km' => [$required, 'integer', 'min:0'],
            'service_date' => [$required, 'date'],
            'interval_km' => [$required, 'integer', 'min:1'],
            'supplier' => ['nullable', 'string', 'max:160'],
            'invoice_number' => ['nullable', 'string', 'max:80'],
            'cost_amount' => ['nullable', 'numeric', 'min:0'],
            'work_order_number' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
