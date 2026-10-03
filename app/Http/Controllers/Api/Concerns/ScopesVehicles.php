<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Vehicle;
use Illuminate\Http\Request;

trait ScopesVehicles
{
    private function findVehicle(Request $request, string $id): Vehicle
    {
        return Vehicle::query()
            ->tenant($request->user()->company_id, $request->user()->branch_id)
            ->whereKey($id)
            ->firstOrFail();
    }
}
