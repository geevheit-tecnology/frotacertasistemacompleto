<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(Contract::query()->tenant($request->user()->company_id, $request->user()->branch_id)->orderBy('ends_at')->paginate(25));
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', 'uuid'],
            'number' => ['required', 'string', 'max:80'],
            'type' => ['required', 'string', 'max:80'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:32'],
            'file_path' => ['nullable', 'string', 'max:500'],
        ]);
        $contract = Contract::create($data + [
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
            'branch_id' => $request->user()->branch_id,
        ]);
        $auditor->recordCreated($request, $contract, 'api.v1.contracts.store');
        return response()->json($contract, 201);
    }
}
