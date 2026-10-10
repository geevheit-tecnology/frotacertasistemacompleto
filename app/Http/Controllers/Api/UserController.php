<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesTenantBranch;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ResolvesTenantBranch;

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            User::query()
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
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'branch_id' => ['nullable', 'uuid'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);
        $data['branch_id'] = $this->resolveBranchId($request->user(), $data['branch_id'] ?? null);

        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'group_id' => $request->user()->group_id,
            'company_id' => $request->user()->company_id,
            'status' => $data['status'] ?? 'ACTIVE',
        ]);

        $auditor->recordCreated($request, $user, 'api.v1.users.store');

        return response()->json($user, 201);
    }

    public function show(Request $request, string $user): JsonResponse
    {
        return response()->json($this->findScoped($request, $user));
    }

    public function update(Request $request, Auditor $auditor, string $user): JsonResponse
    {
        $model = $this->findScoped($request, $user);
        $before = $model->toArray();
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'email' => ['sometimes', 'email', 'max:180', 'unique:users,email,'.$model->id],
            'password' => ['nullable', 'string', 'min:8'],
            'branch_id' => ['nullable', 'uuid'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);
        if (array_key_exists('branch_id', $data)) {
            $data['branch_id'] = $this->resolveBranchId($request->user(), $data['branch_id']);
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $model->fill($data);
        $model->save();
        $auditor->recordUpdated($request, $model, $before, 'api.v1.users.update');

        return response()->json($model);
    }

    public function destroy(Request $request, Auditor $auditor, string $user): JsonResponse
    {
        $model = $this->findScoped($request, $user);
        $before = $model->toArray();
        $model->delete();
        $auditor->recordDeleted($request, $model, $before, 'api.v1.users.destroy');

        return response()->json(status: 204);
    }

    private function findScoped(Request $request, string $id): User
    {
        return User::query()
            ->where('group_id', $request->user()->group_id)
            ->where('company_id', $request->user()->company_id)
            ->whereKey($id)
            ->firstOrFail();
    }
}
