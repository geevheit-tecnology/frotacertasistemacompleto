<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Support\Auditor;
use App\Workflow\Application\RuleEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:32'],
            'severity' => ['nullable', 'string', 'max:20'],
            'branch_id' => ['nullable', 'uuid'],
        ]);

        $query = Alert::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);

        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }

        if (isset($data['severity'])) {
            $query->where('severity', $data['severity']);
        }

        return response()->json($query->latest('alert_date')->paginate(25));
    }

    public function show(Request $request, string $alert): JsonResponse
    {
        return response()->json($this->findScoped($request, $alert));
    }

    public function update(Request $request, Auditor $auditor, string $alert): JsonResponse
    {
        $model = $this->findScoped($request, $alert);
        $before = $model->toArray();
        $data = $request->validate([
            'status' => ['required', 'string', 'max:32'],
            'assignee_id' => ['nullable', 'uuid'],
        ]);

        $model->fill($data);
        $model->save();
        $auditor->recordUpdated($request, $model, $before, 'api.v1.alerts.update');

        return response()->json($model);
    }

    public function createTask(Request $request, Auditor $auditor, RuleEngine $engine, string $alert): JsonResponse
    {
        $model = $this->findScoped($request, $alert);
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $task = $engine->createTaskFromAlert($model, [], $data);
        $auditor->recordCreated($request, $task, 'api.v1.alerts.tasks.store');

        return response()->json($task, 201);
    }

    private function findScoped(Request $request, string $id): Alert
    {
        return Alert::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
    }
}
