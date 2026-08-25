<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Intelligence\Application\TachographAlertService;
use App\Models\TachographRecord;
use App\Models\Vehicle;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TachographRecordController extends Controller
{
    public function __construct(private readonly TachographAlertService $alertService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['nullable', 'uuid'],
            'status' => ['nullable', 'string', 'max:32'],
            'branch_id' => ['nullable', 'uuid'],
        ]);

        $query = TachographRecord::query()
            ->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id)
            ->with('vehicle:id,plate');

        if (isset($data['vehicle_id'])) {
            $query->where('vehicle_id', $data['vehicle_id']);
        }
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }

        return response()->json($query->orderBy('expires_at')->paginate(25));
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $this->validatedData($request);
        $vehicle = $this->findVehicle($request, $data['vehicle_id']);

        $record = TachographRecord::create($data + [
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
            'branch_id' => $vehicle->branch_id,
        ]);

        $auditor->recordCreated($request, $record, 'api.v1.tachographs.store');
        $this->alertService->sync($record->load('vehicle'));

        return response()->json($record->refresh()->load('vehicle'), 201);
    }

    public function show(Request $request, string $tachograph): JsonResponse
    {
        return response()->json($this->findScoped($request, $tachograph)->load('vehicle'));
    }

    public function update(Request $request, Auditor $auditor, string $tachograph): JsonResponse
    {
        $record = $this->findScoped($request, $tachograph);
        $before = $record->toArray();
        $data = $this->validatedData($request, true);

        if (isset($data['vehicle_id'])) {
            $vehicle = $this->findVehicle($request, $data['vehicle_id']);
            $data['branch_id'] = $vehicle->branch_id;
        }

        $record->fill($data);
        $record->save();
        $auditor->recordUpdated($request, $record, $before, 'api.v1.tachographs.update');
        $this->alertService->sync($record->refresh()->load('vehicle'));

        return response()->json($record->load('vehicle'));
    }

    public function destroy(Request $request, Auditor $auditor, string $tachograph): JsonResponse
    {
        $record = $this->findScoped($request, $tachograph);
        $before = $record->toArray();
        $record->delete();
        $auditor->recordDeleted($request, $record, $before, 'api.v1.tachographs.destroy');

        return response()->json(status: 204);
    }

    private function findScoped(Request $request, string $id): TachographRecord
    {
        return TachographRecord::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
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
            'vehicle_id' => [$partial ? 'sometimes' : 'required', 'uuid'],
            'number' => [$partial ? 'sometimes' : 'required', 'string', 'max:80'],
            'manufacturer' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'installed_at' => ['nullable', 'date'],
            'calibrated_at' => ['nullable', 'date'],
            'certificate_number' => ['nullable', 'string', 'max:120'],
            'expires_at' => [$partial ? 'sometimes' : 'required', 'date'],
            'last_maintenance_at' => ['nullable', 'date'],
            'history' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);
    }
}
