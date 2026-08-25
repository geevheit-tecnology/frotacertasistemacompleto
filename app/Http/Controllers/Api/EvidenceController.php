<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evidence;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvidenceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entity' => ['nullable', 'string', 'max:120'],
            'entity_id' => ['nullable', 'uuid'],
        ]);
        $query = Evidence::query()->tenant($request->user()->company_id, $request->user()->branch_id);
        if (isset($data['entity'])) {
            $query->where('entity', $data['entity']);
        }
        if (isset($data['entity_id'])) {
            $query->where('entity_id', $data['entity_id']);
        }

        return response()->json($query->latest()->paginate(25));
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $request->validate([
            'entity' => ['required', 'string', 'max:120'],
            'entity_id' => ['required', 'uuid'],
            'type' => ['required', 'string', 'max:40'],
            'file_name' => ['nullable', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:120'],
            'storage_path' => ['required', 'string', 'max:500'],
            'metadata' => ['nullable', 'array'],
        ]);
        $evidence = Evidence::create($data + [
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
            'branch_id' => $request->user()->branch_id,
        ]);
        $auditor->recordCreated($request, $evidence, 'api.v1.evidences.store');

        return response()->json($evidence, 201);
    }
}
