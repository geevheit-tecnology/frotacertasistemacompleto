<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['status' => ['nullable', 'string', 'max:32'], 'vehicle_id' => ['nullable', 'uuid']]);
        $query = FinancialTransaction::query()->tenant($request->user()->company_id, $request->user()->branch_id);
        if (isset($data['status'])) $query->where('status', $data['status']);
        if (isset($data['vehicle_id'])) $query->where('vehicle_id', $data['vehicle_id']);
        return response()->json($query->latest()->paginate(25));
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['nullable', 'uuid'],
            'work_order_id' => ['nullable', 'uuid'],
            'supplier_id' => ['nullable', 'uuid'],
            'cost_center_id' => ['nullable', 'uuid'],
            'type' => ['required', 'string', 'max:20'],
            'category' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_at' => ['nullable', 'date'],
            'paid_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:32'],
            'origin' => ['nullable', 'string', 'max:120'],
        ]);
        $model = FinancialTransaction::create($data + ['group_id' => $request->user()->group_id, 'company_id' => $request->user()->company_id, 'branch_id' => $request->user()->branch_id]);
        $auditor->recordCreated($request, $model, 'api.v1.financial-transactions.store');
        return response()->json($model, 201);
    }
}
