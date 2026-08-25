<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:32'],
            'priority' => ['nullable', 'string', 'max:32'],
        ]);
        $query = Task::query()->tenant($request->user()->company_id, $request->user()->branch_id);
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (isset($data['priority'])) {
            $query->where('priority', $data['priority']);
        }

        return response()->json($query->orderBy('due_at')->latest()->paginate(25));
    }

    public function show(Request $request, string $task): JsonResponse
    {
        return response()->json($this->findScoped($request, $task));
    }

    public function update(Request $request, Auditor $auditor, string $task): JsonResponse
    {
        $model = $this->findScoped($request, $task);
        $before = $model->toArray();
        $data = $request->validate([
            'status' => ['required', 'string', 'max:32'],
            'assignee_id' => ['nullable', 'uuid'],
            'due_at' => ['nullable', 'date'],
        ]);

        $model->fill($data);
        $model->save();
        $auditor->recordUpdated($request, $model, $before, 'api.v1.tasks.update');

        return response()->json($model);
    }

    private function findScoped(Request $request, string $id): Task
    {
        return Task::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($id)->firstOrFail();
    }
}
