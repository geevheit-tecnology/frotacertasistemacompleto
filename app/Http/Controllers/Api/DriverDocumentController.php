<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverDocument;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            DriverDocument::query()
                ->where('company_id', $request->user()->company_id)
                ->orderBy('expires_at')
                ->paginate(25)
        );
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $request->validate([
            'driver_id' => ['required', 'uuid'],
            'document_type' => ['required', 'string', 'max:80'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:32'],
            'file_path' => ['nullable', 'string'],
        ]);

        $document = DriverDocument::create($data + [
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
            'branch_id' => $request->user()->branch_id,
            'responsible_user_id' => $request->user()->id,
        ]);

        $auditor->recordCreated($request, $document, 'api.v1.driver-documents.store');

        return response()->json($document, 201);
    }

    public function show(Request $request, string $driver_document): JsonResponse
    {
        return response()->json($this->findScoped($request, $driver_document));
    }

    public function update(Request $request, Auditor $auditor, string $driver_document): JsonResponse
    {
        $model = $this->findScoped($request, $driver_document);
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
        $auditor->recordUpdated($request, $model, $before, 'api.v1.driver-documents.update');

        return response()->json($model);
    }

    public function destroy(Request $request, Auditor $auditor, string $driver_document): JsonResponse
    {
        $model = $this->findScoped($request, $driver_document);
        $before = $model->toArray();
        $model->delete();
        $auditor->recordDeleted($request, $model, $before, 'api.v1.driver-documents.destroy');

        return response()->json(status: 204);
    }

    private function findScoped(Request $request, string $id): DriverDocument
    {
        return DriverDocument::query()
            ->where('company_id', $request->user()->company_id)
            ->whereKey($id)
            ->firstOrFail();
    }
}
