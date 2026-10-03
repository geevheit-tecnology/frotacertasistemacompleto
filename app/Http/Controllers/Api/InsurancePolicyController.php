<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ScopesVehicles;
use App\Http\Controllers\Controller;
use App\Models\InsurancePolicy;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsurancePolicyController extends Controller
{
    use ScopesVehicles;

    public function index(Request $request): JsonResponse
    {
        return response()->json(InsurancePolicy::query()->tenant($request->user()->company_id, $request->user()->branch_id)->orderBy('expires_at')->paginate(25));
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['nullable', 'uuid'],
            'policy_number' => ['required', 'string', 'max:100'],
            'provider' => ['required', 'string', 'max:160'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['required', 'date'],
            'premium_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:32'],
            'file_path' => ['nullable', 'string', 'max:500'],
        ]);
        $branchId = $request->user()->branch_id;
        if (isset($data['vehicle_id'])) {
            $branchId = $this->findVehicle($request, $data['vehicle_id'])->branch_id;
        }
        $policy = InsurancePolicy::create($data + [
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
            'branch_id' => $branchId,
        ]);
        $auditor->recordCreated($request, $policy, 'api.v1.insurance-policies.store');
        return response()->json($policy, 201);
    }
}
