<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CostCenterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(CostCenter::query()->tenant($request->user()->company_id, $request->user()->branch_id)->orderBy('name')->paginate(25));
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'code' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);
        $model = CostCenter::create($data + ['group_id' => $request->user()->group_id, 'company_id' => $request->user()->company_id, 'branch_id' => $request->user()->branch_id]);
        $auditor->recordCreated($request, $model, 'api.v1.cost-centers.store');
        return response()->json($model, 201);
    }
}
