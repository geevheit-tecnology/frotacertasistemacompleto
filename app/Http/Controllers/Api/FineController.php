<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ScopesVehicles;
use App\Http\Controllers\Controller;
use App\Models\Fine;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FineController extends Controller
{
    use ScopesVehicles;

    public function index(Request $request): JsonResponse
    {
        return response()->json(Fine::query()->tenant($request->user()->company_id, $request->user()->branch_id)->orderBy('due_at')->paginate(25));
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'uuid'],
            'driver_id' => ['nullable', 'uuid'],
            'notice_number' => ['required', 'string', 'max:120'],
            'occurred_at' => ['required', 'date'],
            'due_at' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:5000'],
            'file_path' => ['nullable', 'string', 'max:500'],
        ]);
        $vehicle = $this->findVehicle($request, $data['vehicle_id']);
        $fine = Fine::create($data + [
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
            'branch_id' => $vehicle->branch_id,
        ]);
        $auditor->recordCreated($request, $fine, 'api.v1.fines.store');
        return response()->json($fine, 201);
    }
}
