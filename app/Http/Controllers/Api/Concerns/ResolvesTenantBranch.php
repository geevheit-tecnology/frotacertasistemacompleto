<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

trait ResolvesTenantBranch
{
    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    private function resolveBranchId(User $user, ?string $requestedBranchId): ?string
    {
        if ($user->branch_id !== null) {
            if ($requestedBranchId !== null && $requestedBranchId !== $user->branch_id) {
                throw new AuthorizationException('Branch is outside the authenticated user scope.');
            }

            return $user->branch_id;
        }

        if ($requestedBranchId === null) {
            return null;
        }

        $exists = Branch::query()
            ->where('group_id', $user->group_id)
            ->where('company_id', $user->company_id)
            ->whereKey($requestedBranchId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'branch_id' => ['The selected branch is invalid.'],
            ]);
        }

        return $requestedBranchId;
    }
}
