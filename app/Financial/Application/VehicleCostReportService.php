<?php

namespace App\Financial\Application;

use App\Models\FinancialTransaction;
use App\Models\FuelRecord;
use App\Models\Invoice;
use App\Models\Vehicle;

class VehicleCostReportService
{
    /**
     * @return array<string, mixed>
     */
    public function summarize(Vehicle $vehicle): array
    {
        $fuelAmount = (float) FuelRecord::query()->where('vehicle_id', $vehicle->id)->sum('total_amount');
        $financialAmount = (float) FinancialTransaction::query()->where('vehicle_id', $vehicle->id)->sum('amount');
        $invoiceAmount = (float) Invoice::query()->where('vehicle_id', $vehicle->id)->sum('amount');
        $km = max((int) $vehicle->current_odometer_km, 1);
        $total = $financialAmount + $invoiceAmount;

        return [
            'vehicle_id' => $vehicle->id,
            'plate' => $vehicle->plate,
            'odometer_km' => $vehicle->current_odometer_km,
            'fuel_cost' => round($fuelAmount, 2),
            'financial_cost' => round($financialAmount, 2),
            'invoice_cost' => round($invoiceAmount, 2),
            'total_cost' => round($total, 2),
            'cost_per_km' => round($total / $km, 4),
        ];
    }
}
