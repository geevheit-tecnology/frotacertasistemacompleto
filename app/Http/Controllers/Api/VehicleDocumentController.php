<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleDocument;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            VehicleDocument::query()
                ->where('company_id', $request->user()->company_id)
                ->orderBy('expires_at')
                ->paginate(25)
        );
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'uuid'],
            'document_type' => ['required', 'string', 'max:80'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:32'],
            'file_path' => ['nullable', 'string'],
        ]);

        $document = VehicleDocument::create($data + [
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
            'branch_id' => $request->user()->branch_id,
            'responsible_user_id' => $request->user()->id,
        ]);

        $auditor->recordCreated($request, $document, 'api.v1.vehicle-documents.store');

        return response()->json($document, 201);
    }

    public function show(Request $request, string $vehicle_document): JsonResponse
    {
        return response()->json($this->findScoped($request, $vehicle_document));
    }

    public function update(Request $request, Auditor $auditor, string $vehicle_document): JsonResponse
    {
        $model = $this->findScoped($request, $vehicle_document);
        $before = $model->toArray();
        $data = $request->validate([
            'document_type' => ['sometimes', 'string', 'max:80'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:32'],
            'file_path' => ['nullable', 'string'],
        ]);

        $model->fill($data);
        $model->save();
        $auditor->recordUpdated($request, $model, $before, 'api.v1.vehicle-documents.update');

        return response()->json($model);
    }

    public function destroy(Request $request, Auditor $auditor, string $vehicle_document): JsonResponse
    {
        $model = $this->findScoped($request, $vehicle_document);
        $before = $model->toArray();
        $model->delete();
        $auditor->recordDeleted($request, $model, $before, 'api.v1.vehicle-documents.destroy');

        return response()->json(status: 204);
    }

    private function findScoped(Request $request, string $id): VehicleDocument
    {
        return VehicleDocument::query()
            ->where('company_id', $request->user()->company_id)
            ->whereKey($id)
            ->firstOrFail();
    }
}
