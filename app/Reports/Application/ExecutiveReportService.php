<?php

namespace App\Reports\Application;

use App\Models\Alert;
use App\Models\ChecklistExecution;
use App\Models\FinancialTransaction;
use App\Models\FuelRecord;
use App\Models\Insight;
use App\Models\Task;
use App\Models\Vehicle;

class ExecutiveReportService
{
    /**
     * @return array<string, mixed>
     */
    public function summarize(string $companyId, ?string $branchId = null): array
    {
        $vehicles = Vehicle::query()->tenant($companyId, $branchId);
        $alerts = Alert::query()->tenant($companyId, $branchId);
        $tasks = Task::query()->tenant($companyId, $branchId);
        $financial = FinancialTransaction::query()->tenant($companyId, $branchId);
        $fuel = FuelRecord::query()->tenant($companyId, $branchId);

        return [
            'fleet' => [
                'total' => (clone $vehicles)->count(),
                'available' => (clone $vehicles)->where('status', 'ACTIVE')->count(),
                'maintenance' => (clone $vehicles)->where('status', 'MAINTENANCE')->count(),
                'blocked' => (clone $vehicles)->where('status', 'BLOCKED')->count(),
            ],
            'risks' => [
                'critical_alerts' => (clone $alerts)->where('status', 'ABERTO')->where('severity', 'CRITICO')->count(),
                'open_alerts' => (clone $alerts)->where('status', 'ABERTO')->count(),
                'open_insights' => Insight::query()->tenant($companyId, $branchId)->where('status', 'OPEN')->count(),
            ],
            'operations' => [
                'open_tasks' => (clone $tasks)->where('status', 'OPEN')->count(),
                'completed_checklists' => ChecklistExecution::query()->tenant($companyId, $branchId)->where('status', 'COMPLETED')->count(),
            ],
            'costs' => [
                'open_payables' => (clone $financial)->where('type', 'PAYABLE')->where('status', 'OPEN')->sum('amount'),
                'paid_expenses' => (clone $financial)->where('type', 'PAYABLE')->where('status', 'PAID')->sum('amount'),
                'fuel_amount' => (clone $fuel)->sum('total_amount'),
                'fuel_liters' => (clone $fuel)->sum('quantity_liters'),
            ],
        ];
    }
}
