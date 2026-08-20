<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Supplier::query()->tenant($request->user()->company_id, $request->user()->branch_id)->orderBy('name')->paginate(25)
        );
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $this->validatedData($request);
        $supplier = Supplier::create($data + [
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
            'branch_id' => $data['branch_id'] ?? $request->user()->branch_id,
        ]);
        $auditor->recordCreated($request, $supplier, 'api.v1.suppliers.store');
        return response()->json($supplier, 201);
    }

    public function show(Request $request, string $supplier): JsonResponse
    {
        return response()->json($this->findScoped($request, $supplier));
    }

    public function update(Request $request, Auditor $auditor, string $supplier): JsonResponse
    {
        $model = $this->findScoped($request, $supplier);
        $before = $model->toArray();
        $model->fill($this->validatedData($request, true));
        $model->save();
        $auditor->recordUpdated($request, $model, $before, 'api.v1.suppliers.update');
        return response()->json($model);
    }

    public function destroy(Request $request, Auditor $auditor, string $supplier): JsonResponse
    {
        $model = $this->findScoped($request, $supplier);
        $before = $model->toArray();
        $model->delete();
        $auditor->recordDeleted($request, $model, $before, 'api.v1.suppliers.destroy');
        return response()->json(status: 204);
    }

    private function findScoped(Request $request, string $id): Supplier
    {
        return Supplier::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'branch_id' => ['nullable', 'uuid'],
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:160'],
            'document_number' => ['nullable', 'string', 'max:32'],
            'type' => ['nullable', 'string', 'max:40'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);
    }
}
