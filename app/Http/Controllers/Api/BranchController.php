<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Branch::query()
                ->where('group_id', $request->user()->group_id)
                ->where('company_id', $request->user()->company_id)
                ->orderBy('name')
                ->paginate(25)
        );
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
        ]);

        $branch = Branch::create($data + [
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
        ]);

        $auditor->recordCreated($request, $branch, 'api.v1.branches.store');

        return response()->json($branch, 201);
    }

    public function show(Request $request, string $branch): JsonResponse
    {
        return response()->json($this->findScoped($request, $branch));
    }

    public function update(Request $request, Auditor $auditor, string $branch): JsonResponse
    {
        $model = $this->findScoped($request, $branch);
        $before = $model->toArray();
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
        ]);

        $model->fill($data);
        $model->save();
        $auditor->recordUpdated($request, $model, $before, 'api.v1.branches.update');

        return response()->json($model);
    }

    public function destroy(Request $request, Auditor $auditor, string $branch): JsonResponse
    {
        $model = $this->findScoped($request, $branch);
        $before = $model->toArray();
        $model->delete();
        $auditor->recordDeleted($request, $model, $before, 'api.v1.branches.destroy');

        return response()->json(status: 204);
    }

    private function findScoped(Request $request, string $id): Branch
    {
        return Branch::query()
            ->where('group_id', $request->user()->group_id)
            ->where('company_id', $request->user()->company_id)
            ->whereKey($id)
            ->firstOrFail();
    }
}
