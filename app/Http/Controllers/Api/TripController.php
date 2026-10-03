<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ScopesVehicles;
use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends Controller
{
    use ScopesVehicles;

    public function index(Request $request): JsonResponse
    {
        return response()->json(Trip::query()->tenant($request->user()->company_id, $request->user()->branch_id)->orderBy('starts_at')->paginate(25));
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'uuid'],
            'driver_id' => ['nullable', 'uuid'],
            'origin' => ['required', 'string', 'max:160'],
            'destination' => ['required', 'string', 'max:160'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:32'],
            'metadata' => ['nullable', 'array'],
        ]);
        $vehicle = $this->findVehicle($request, $data['vehicle_id']);
        $trip = Trip::create($data + [
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
            'branch_id' => $vehicle->branch_id,
        ]);
        $auditor->recordCreated($request, $trip, 'api.v1.trips.store');
        return response()->json($trip, 201);
    }
}
