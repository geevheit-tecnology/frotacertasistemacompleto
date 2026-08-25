<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Insight;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsightController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:32'],
            'type' => ['nullable', 'string', 'max:60'],
            'branch_id' => ['nullable', 'uuid'],
        ]);

        $query = Insight::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);

        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }

        if (isset($data['type'])) {
            $query->where('type', $data['type']);
        }

        return response()->json($query->orderByDesc('priority')->latest()->paginate(25));
    }

    public function show(Request $request, string $insight): JsonResponse
    {
        return response()->json($this->findScoped($request, $insight));
    }

    public function update(Request $request, Auditor $auditor, string $insight): JsonResponse
    {
        $model = $this->findScoped($request, $insight);
        $before = $model->toArray();
        $data = $request->validate([
            'status' => ['required', 'string', 'max:32'],
        ]);

        $model->fill($data);
        $model->save();
        $auditor->recordUpdated($request, $model, $before, 'api.v1.insights.update');

        return response()->json($model);
    }

    private function findScoped(Request $request, string $id): Insight
    {
        return Insight::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
    }
}
