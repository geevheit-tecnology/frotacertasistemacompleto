<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ScopesVehicles;
use App\Http\Controllers\Controller;
use App\Models\TelematicsEvent;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelematicsEventController extends Controller
{
    use ScopesVehicles;

    public function index(Request $request): JsonResponse
    {
        return response()->json(TelematicsEvent::query()->tenant($request->user()->company_id, $request->user()->branch_id)->latest('recorded_at')->paginate(25));
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'uuid'],
            'recorded_at' => ['required', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
            'hourmeter' => ['nullable', 'numeric', 'min:0'],
            'speed_kmh' => ['nullable', 'numeric', 'min:0'],
            'source' => ['nullable', 'string', 'max:80'],
            'payload' => ['nullable', 'array'],
        ]);
        $vehicle = $this->findVehicle($request, $data['vehicle_id']);
        $event = TelematicsEvent::create($data + [
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
            'branch_id' => $vehicle->branch_id,
        ]);
        $auditor->recordCreated($request, $event, 'api.v1.telematics-events.store');
        return response()->json($event, 201);
    }
}
