<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MissionControlController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'uuid'],
        ]);

        $vehicles = Vehicle::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);

        return response()->json([
            'fleet' => [
                'total' => (clone $vehicles)->count(),
                'active' => (clone $vehicles)->where('status', 'ACTIVE')->count(),
                'stopped' => (clone $vehicles)->where('status', 'STOPPED')->count(),
                'blocked' => (clone $vehicles)->where('status', 'BLOCKED')->count(),
                'maintenance' => (clone $vehicles)->where('status', 'MAINTENANCE')->count(),
            ],
        ]);
    }
}
