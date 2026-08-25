<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NonConformity;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NonConformityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:32'],
            'severity' => ['nullable', 'string', 'max:20'],
        ]);
        $query = NonConformity::query()->tenant($request->user()->company_id, $request->user()->branch_id);
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (isset($data['severity'])) {
            $query->where('severity', $data['severity']);
        }

        return response()->json($query->latest()->paginate(25));
    }

    public function show(Request $request, string $nonConformity): JsonResponse
    {
        return response()->json($this->findScoped($request, $nonConformity));
    }

    public function update(Request $request, Auditor $auditor, string $nonConformity): JsonResponse
    {
        $model = $this->findScoped($request, $nonConformity);
        $before = $model->toArray();
        $data = $request->validate([
            'status' => ['required', 'string', 'max:32'],
        ]);
        $model->fill($data);
        $model->save();
        $auditor->recordUpdated($request, $model, $before, 'api.v1.non-conformities.update');

        return response()->json($model);
    }

    private function findScoped(Request $request, string $id): NonConformity
    {
        return NonConformity::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
    }
}
