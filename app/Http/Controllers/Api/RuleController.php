<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rule;
use App\Support\Auditor;
use App\Workflow\Application\RuleEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'trigger_event' => ['nullable', 'string', 'max:160'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        $query = Rule::query()->tenant($request->user()->company_id, $request->user()->branch_id);
        if (isset($data['trigger_event'])) {
            $query->where('trigger_event', $data['trigger_event']);
        }
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }

        return response()->json($query->latest()->paginate(25));
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $this->validatedData($request);
        $rule = Rule::create($data + [
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
            'branch_id' => $request->user()->branch_id,
        ]);
        $auditor->recordCreated($request, $rule, 'api.v1.rules.store');

        return response()->json($rule, 201);
    }

    public function show(Request $request, string $rule): JsonResponse
    {
        return response()->json($this->findScoped($request, $rule));
    }

    public function update(Request $request, Auditor $auditor, string $rule): JsonResponse
    {
        $model = $this->findScoped($request, $rule);
        $before = $model->toArray();
        $model->fill($this->validatedData($request, true));
        $model->save();
        $auditor->recordUpdated($request, $model, $before, 'api.v1.rules.update');

        return response()->json($model);
    }

    public function destroy(Request $request, Auditor $auditor, string $rule): JsonResponse
    {
        $model = $this->findScoped($request, $rule);
        $before = $model->toArray();
        $model->delete();
        $auditor->recordDeleted($request, $model, $before, 'api.v1.rules.destroy');

        return response()->json(status: 204);
    }

    public function execute(Request $request, RuleEngine $engine, string $rule): JsonResponse
    {
        $model = $this->findScoped($request, $rule);
        $data = $request->validate([
            'entity' => ['required', 'string', 'max:120'],
            'entity_id' => ['required', 'uuid'],
            'payload' => ['nullable', 'array'],
        ]);

        return response()->json($engine->execute($model, $data['entity'], $data['entity_id'], $data['payload'] ?? []), 201);
    }

    private function findScoped(Request $request, string $id): Rule
    {
        return Rule::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:160'],
            'trigger_event' => [$partial ? 'sometimes' : 'required', 'string', 'max:160'],
            'conditions' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
            'severity' => ['nullable', 'string', 'max:20'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'requires_approval' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);
    }
}
