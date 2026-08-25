<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\FuelRecord;
use App\Models\Vehicle;
use App\Support\Auditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FuelRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(FuelRecord::query()->tenant($request->user()->company_id, $request->user()->branch_id)->latest('filled_at')->paginate(25));
    }

    public function store(Request $request, Auditor $auditor): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'uuid'],
            'driver_id' => ['nullable', 'uuid'],
            'supplier_id' => ['nullable', 'uuid'],
            'cost_center_id' => ['nullable', 'uuid'],
            'fuel_type' => ['required', 'string', 'max:40'],
            'quantity_liters' => ['required', 'numeric', 'min:0.001'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'odometer_km' => ['required', 'integer', 'min:0'],
            'invoice_number' => ['nullable', 'string', 'max:80'],
            'filled_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ]);
        $vehicle = Vehicle::query()->tenant($request->user()->company_id, $request->user()->branch_id)->whereKey($data['vehicle_id'])->firstOrFail();
        if ((int) $vehicle->current_odometer_km > (int) $data['odometer_km']) {
            return response()->json(['message' => 'Fuel odometer cannot be lower than current vehicle odometer.'], 422);
        }

        $record = DB::transaction(function () use ($request, $auditor, $data, $vehicle): FuelRecord {
            $total = round((float) $data['quantity_liters'] * (float) $data['unit_price'], 2);
            $previous = FuelRecord::query()->where('vehicle_id', $vehicle->id)->latest('filled_at')->first();
            $average = $previous === null ? null : round(((int) $data['odometer_km'] - (int) $previous->odometer_km) / (float) $data['quantity_liters'], 2);
            $fuel = FuelRecord::create($data + [
                'group_id' => $request->user()->group_id,
                'company_id' => $request->user()->company_id,
                'branch_id' => $vehicle->branch_id,
                'total_amount' => $total,
                'average_km_per_liter' => $average,
                'filled_at' => $data['filled_at'] ?? now(),
            ]);
            FinancialTransaction::create([
                'group_id' => $request->user()->group_id,
                'company_id' => $request->user()->company_id,
                'branch_id' => $vehicle->branch_id,
                'vehicle_id' => $vehicle->id,
                'supplier_id' => $data['supplier_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'type' => 'PAYABLE',
                'category' => 'FUEL',
                'description' => 'Abastecimento '.$fuel->fuel_type,
                'amount' => $total,
                'due_at' => now()->toDateString(),
                'status' => 'OPEN',
                'origin' => 'fuel-record',
            ]);
            $vehicle->current_odometer_km = (int) $data['odometer_km'];
            $vehicle->save();
            $auditor->recordCreated($request, $fuel, 'api.v1.fuel-records.store');
            return $fuel;
        });

        return response()->json($record, 201);
    }
}
