<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\ChecklistExecution;
use App\Models\FinancialTransaction;
use App\Models\FuelRecord;
use App\Models\Contract;
use App\Models\Insight;
use App\Models\Invoice;
use App\Models\InsurancePolicy;
use App\Models\MaintenancePlan;
use App\Models\NonConformity;
use App\Models\TachographRecord;
use App\Models\Task;
use App\Models\TelematicsEvent;
use App\Models\Tire;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Carbon\CarbonImmutable;
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
        $alerts = Alert::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);
        $checklists = ChecklistExecution::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);
        $financial = FinancialTransaction::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);
        $fuel = FuelRecord::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);
        $contracts = Contract::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);
        $invoices = Invoice::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);
        $insurancePolicies = InsurancePolicy::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);
        $insights = Insight::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);
        $maintenancePlans = MaintenancePlan::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);
        $nonConformities = NonConformity::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);
        $tachographs = TachographRecord::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);
        $tasks = Task::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);
        $telematics = TelematicsEvent::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);
        $tires = Tire::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);
        $trips = Trip::query()->tenant($request->user()->company_id, $data['branch_id'] ?? $request->user()->branch_id);
        $vehicleDocuments = VehicleDocument::query()->where('company_id', $request->user()->company_id)->when($data['branch_id'] ?? $request->user()->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId));
        $today = CarbonImmutable::today();

        return response()->json([
            'fleet' => [
                'total' => (clone $vehicles)->count(),
                'active' => (clone $vehicles)->where('status', 'ACTIVE')->count(),
                'stopped' => (clone $vehicles)->where('status', 'STOPPED')->count(),
                'blocked' => (clone $vehicles)->where('status', 'BLOCKED')->count(),
                'maintenance' => (clone $vehicles)->where('status', 'MAINTENANCE')->count(),
            ],
            'alerts' => [
                'open' => (clone $alerts)->where('status', 'ABERTO')->count(),
                'critical' => (clone $alerts)->where('status', 'ABERTO')->where('severity', 'CRITICO')->count(),
                'high' => (clone $alerts)->where('status', 'ABERTO')->where('severity', 'ALTO')->count(),
                'medium' => (clone $alerts)->where('status', 'ABERTO')->where('severity', 'MEDIO')->count(),
            ],
            'insights' => [
                'open' => (clone $insights)->where('status', 'OPEN')->count(),
                'high_priority' => (clone $insights)->where('status', 'OPEN')->where('priority', '>=', 80)->count(),
            ],
            'preventive' => [
                'active_plans' => (clone $maintenancePlans)->where('status', 'ACTIVE')->count(),
            ],
            'tachographs' => [
                'expired' => (clone $tachographs)->where('expires_at', '<', $today)->count(),
                'next_90_days' => (clone $tachographs)->whereBetween('expires_at', [$today, $today->addDays(90)])->count(),
            ],
            'compliance' => [
                'vehicle_documents_next_90_days' => (clone $vehicleDocuments)->whereBetween('expires_at', [$today, $today->addDays(90)])->count(),
                'insurance_next_90_days' => (clone $insurancePolicies)->whereBetween('expires_at', [$today, $today->addDays(90)])->count(),
                'contracts_next_90_days' => (clone $contracts)->whereBetween('ends_at', [$today, $today->addDays(90)])->count(),
            ],
            'operations' => [
                'scheduled_trips' => (clone $trips)->where('status', 'SCHEDULED')->count(),
                'telematics_events' => (clone $telematics)->count(),
            ],
            'tasks' => [
                'open' => (clone $tasks)->where('status', 'OPEN')->count(),
                'overdue' => (clone $tasks)->where('status', 'OPEN')->where('due_at', '<', $today)->count(),
            ],
            'checklists' => [
                'completed' => (clone $checklists)->where('status', 'COMPLETED')->count(),
                'non_conformities_open' => (clone $nonConformities)->where('status', 'OPEN')->count(),
                'critical_non_conformities' => (clone $nonConformities)->where('status', 'OPEN')->where('severity', 'CRITICO')->count(),
            ],
            'tires' => [
                'total' => (clone $tires)->count(),
                'installed' => (clone $tires)->where('status', 'INSTALADO')->count(),
                'critical_tread' => (clone $tires)->where('tread_depth_mm', '<=', 1.6)->count(),
                'attention_tread' => (clone $tires)->whereBetween('tread_depth_mm', [1.61, 3.0])->count(),
            ],
            'financial' => [
                'open_payables' => (clone $financial)->where('type', 'PAYABLE')->where('status', 'OPEN')->sum('amount'),
                'paid_expenses' => (clone $financial)->where('type', 'PAYABLE')->where('status', 'PAID')->sum('amount'),
                'invoices_pending' => (clone $invoices)->where('status', 'PENDING')->count(),
                'invoices_without_link' => (clone $invoices)->whereJsonContains('flags', 'MISSING_OPERATIONAL_LINK')->count(),
            ],
            'fuel' => [
                'records' => (clone $fuel)->count(),
                'total_liters' => (clone $fuel)->sum('quantity_liters'),
                'total_amount' => (clone $fuel)->sum('total_amount'),
            ],
        ]);
    }
}
