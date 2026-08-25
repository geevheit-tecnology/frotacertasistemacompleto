<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChecklistTemplate;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            ChecklistTemplate::query()
                ->tenant($request->user()->company_id, $request->user()->branch_id)
                ->orderBy('name')
                ->paginate(25)
        );
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'vehicle_type' => ['nullable', 'string', 'max:80'],
            'items' => ['required', 'array', 'min:1'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        $template = ChecklistTemplate::create($data + [
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
            'branch_id' => $request->user()->branch_id,
        ]);
        $auditor->recordCreated($request, $template, 'api.v1.checklist-templates.store');

        return response()->json($template, 201);
    }

    public function show(Request $request, string $checklistTemplate): JsonResponse
    {
        return response()->json($this->findScoped($request, $checklistTemplate));
    }

    private function findScoped(Request $request, string $id): ChecklistTemplate
    {
        return ChecklistTemplate::query()
            ->tenant($request->user()->company_id, $request->user()->branch_id)
            ->whereKey($id)
            ->firstOrFail();
    }
}
